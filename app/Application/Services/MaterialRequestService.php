<?php

namespace App\Application\Services;

use App\Application\DTOs\MaterialRequest\MaterialRequestDto;
use App\Application\DTOs\MaterialRequest\MaterialRequestItemDto;
use App\Domain\Enums\MaterialRequestStatus;
use App\Domain\Enums\MovementType;
use App\Models\MaterialRequest;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Caso de uso de Solicitação de Materiais.
 * Implementa o ciclo de 6 etapas com transições validadas:
 *   solicitado -> aprovado -> separado -> conferido -> entregue -> finalizado
 * No "entregue" consome o estoque (MovementType::EXIT) via StockService,
 * garantindo atomicidade e rastreabilidade no Kardex.
 */
class MaterialRequestService
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    public function create(MaterialRequestDto $dto): MaterialRequest
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();
            if (empty($data['code'])) {
                $data['code'] = $this->generateCode();
            }
            $request = MaterialRequest::create($data);
            $this->syncItems($request, $dto->items ?? []);

            return $request->load('items.product');
        });
    }

    /**
     * Gera código legível e único: MR-AAMMDD-XXXX (sequencial por dia).
     */
    protected function generateCode(): string
    {
        $prefix = 'MR-'.now()->format('Ymd');
        $last = MaterialRequest::where('code', 'like', "{$prefix}-%")
            ->orderByDesc('id')
            ->value('code');

        $seq = 1;
        if ($last) {
            $seq = (int) substr($last, strlen($prefix) + 1) + 1;
        }

        return "{$prefix}-".str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function find(int $id): MaterialRequest
    {
        return MaterialRequest::with(['items.product.unit', 'requester', 'approver'])
            ->findOrFail($id);
    }

    public function list(array $filters = [], int $perPage = 15)
    {
        $query = MaterialRequest::with(['requester', 'sector', 'costCenter']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['requester_id'])) {
            $query->where('requester_id', $filters['requester_id']);
        }
        if (! empty($filters['sector_id'])) {
            $query->where('sector_id', $filters['sector_id']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Avança a solicitação para "aprovado": valida disponibilidade e fixa
     * a quantidade aprovada por item.
     */
    public function approve(int $id, ?string $observation = null): MaterialRequest
    {
        return DB::transaction(function () use ($id, $observation) {
            $request = $this->requireStatus($id, MaterialRequestStatus::SOLICITADO);

            foreach ($request->items as $item) {
                $this->assertAvailable($item->product, $item->quantity_requested, $item->warehouse_id);
                $item->quantity_approved = $item->quantity_requested;
                $item->saveQuietly();
            }

            $request->status = MaterialRequestStatus::APROVADO->value;
            $request->approver_id = auth()->id();
            $request->approved_at = now();
            if ($observation) {
                $request->observation = $observation;
            }
            $request->saveQuietly();

            return $request->load('items.product');
        });
    }

    /** Avança para "separado" (almoxarife separou fisicamente). */
    public function separate(int $id): MaterialRequest
    {
        return $this->transition($id, MaterialRequestStatus::APROVADO, MaterialRequestStatus::SEPARADO);
    }

    /** Avança para "conferido" (conferência de qualidade/quantidade). */
    public function check(int $id): MaterialRequest
    {
        return $this->transition($id, MaterialRequestStatus::SEPARADO, MaterialRequestStatus::CONFERIDO);
    }

    /**
     * Avança para "entregue" e CONSOME o estoque: gera uma saída (EXIT) no
     * Kardex por item, na quantidade aprovada (ou entregue). Idempotente por
     * item — só consome o saldo ainda pendente de entrega.
     */
    public function deliver(int $id): MaterialRequest
    {
        return DB::transaction(function () use ($id) {
            $request = $this->requireStatus($id, MaterialRequestStatus::CONFERIDO);

            foreach ($request->items as $item) {
                $pending = $item->pending();
                if ($pending <= 0) {
                    continue;
                }
                $this->assertAvailable($item->product, $pending, $item->warehouse_id);

                $this->stock->apply([
                    'product_id' => $item->product_id,
                    'type' => MovementType::EXIT->value,
                    'reason' => 'Solicitação de material',
                    'source_type' => 'material_request',
                    'warehouse_id' => $item->warehouse_id ?? $item->product->warehouse_id,
                    'quantity' => $pending,
                    'document_number' => $request->code,
                    'cost_center_id' => $request->cost_center_id,
                    'employee_id' => $request->employee_id,
                    'observation' => "MR {$request->code} | item #{$item->id}",
                ]);

                $item->quantity_delivered = (float) $item->quantity_delivered + $pending;
                $item->saveQuietly();
            }

            $request->status = MaterialRequestStatus::ENTREGUE->value;
            $request->delivered_at = now();
            $request->saveQuietly();

            return $request->load('items.product');
        });
    }

    /** Avança para "finalizado" (fechamento administrativo). */
    public function finish(int $id): MaterialRequest
    {
        return DB::transaction(function () use ($id) {
            $request = $this->requireStatus($id, MaterialRequestStatus::ENTREGUE);
            $request->status = MaterialRequestStatus::FINALIZADO->value;
            $request->finished_at = now();
            $request->saveQuietly();

            return $request->load('items.product');
        });
    }

    /** Cancela a solicitação (terminal). Não reverte estoque já entregue. */
    public function cancel(int $id, ?string $observation = null): MaterialRequest
    {
        return DB::transaction(function () use ($id, $observation) {
            $request = MaterialRequest::findOrFail($id);
            if ($request->statusEnum()->isTerminal()) {
                throw ValidationException::withMessages([
                    'status' => ['Solicitação já está em estado terminal e não pode ser cancelada.'],
                ]);
            }
            $request->status = MaterialRequestStatus::CANCELADO->value;
            if ($observation) {
                $request->observation = $observation;
            }
            $request->saveQuietly();

            return $request->fresh();
        });
    }

    protected function transition(int $id, MaterialRequestStatus $from, MaterialRequestStatus $to): MaterialRequest
    {
        return DB::transaction(function () use ($id, $from, $to) {
            $request = $this->requireStatus($id, $from);
            $request->status = $to->value;
            $request->saveQuietly();

            return $request->load('items.product');
        });
    }

    protected function requireStatus(int $id, MaterialRequestStatus $expected): MaterialRequest
    {
        $request = MaterialRequest::with('items.product', 'items.warehouse')->findOrFail($id);
        if ($request->status !== $expected->value) {
            throw ValidationException::withMessages([
                'status' => ["Transição inválida: estado atual '{$request->status}', esperado '{$expected->value}'."],
            ]);
        }

        return $request;
    }

    protected function assertAvailable(Product $product, float $qty, ?int $warehouseId): void
    {
        $warehouseId ??= $product->warehouse_id;
        $balance = $product->stockBalances()
            ->where('warehouse_id', $warehouseId)
            ->first();

        $available = $balance?->available ?? $product->available_stock;
        if ((float) $available < $qty) {
            throw ValidationException::withMessages([
                'stock' => ["Estoque insuficiente para '{$product->name}': disponível {$available}, necessário {$qty}."],
            ]);
        }
    }

    /**
     * @param  MaterialRequestItemDto[]  $items
     */
    protected function syncItems(MaterialRequest $request, array $items): void
    {
        $request->items()->delete();
        foreach ($items as $item) {
            $request->items()->create($item->toArray());
        }
    }
}
