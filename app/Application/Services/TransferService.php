<?php

namespace App\Application\Services;

use App\Application\DTOs\Transfer\TransferDto;
use App\Application\DTOs\Transfer\TransferItemDto;
use App\Domain\Enums\MovementType;
use App\Domain\Enums\TransferStatus;
use App\Models\Product;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Caso de uso de Transferência entre almoxarifados.
 *
 * Fluxo de 2 pernas sobre o StockService (single source of truth do Kardex):
 *  - ship  (pending -> in_transit): aplica TRANSFER_OUT na ORIGEM. O saldo sai
 *          da origem e fica "em trânsito" (sem crédito antecipado no destino).
 *  - receive (in_transit -> received): aplica TRANSFER_IN no DESTINO pela
 *          quantidade efetivamente recebida (suporta recebimento parcial).
 *
 * Não duplica regra de saldo: apenas orquestra movimentações. O ajuste de
 * eventual perda em trânsito (qty - received) fica visível no destino.
 */
class TransferService
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    public function create(TransferDto $dto): Transfer
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();
            if (empty($data['code'])) {
                $data['code'] = $this->generateCode();
            }
            if ($dto->originWarehouseId === $dto->destinationWarehouseId) {
                throw ValidationException::withMessages([
                    'destination_warehouse_id' => ['Origem e destino devem ser almoxarifados diferentes.'],
                ]);
            }

            $transfer = Transfer::create($data);
            $this->syncItems($transfer, $dto->items);

            return $transfer->load('items.product');
        });
    }

    protected function generateCode(): string
    {
        $prefix = 'TR-'.now()->format('Ymd');
        $last = Transfer::where('code', 'like', "{$prefix}-%")
            ->orderByDesc('id')
            ->value('code');

        $seq = 1;
        if ($last) {
            $seq = (int) substr($last, strlen($prefix) + 1) + 1;
        }

        return "{$prefix}-".str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function find(int $id): Transfer
    {
        return Transfer::with(['items.product.unit', 'originWarehouse', 'destinationWarehouse', 'requester', 'sender', 'receiver'])
            ->findOrFail($id);
    }

    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Transfer::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['origin_warehouse_id'])) {
            $query->where('origin_warehouse_id', $filters['origin_warehouse_id']);
        }
        if (! empty($filters['destination_warehouse_id'])) {
            $query->where('destination_warehouse_id', $filters['destination_warehouse_id']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Embarque: debita a origem (TRANSFER_OUT) por item. O destino só é
     * creditado no receive, então o saldo fica "em trânsito".
     */
    public function ship(int $id): Transfer
    {
        return DB::transaction(function () use ($id) {
            $transfer = $this->requireStatus($id, TransferStatus::PENDING);

            foreach ($transfer->items as $item) {
                $this->assertAvailable($item->product, (float) $item->quantity, $transfer->origin_warehouse_id);

                $this->stock->apply([
                    'product_id' => $item->product_id,
                    'type' => MovementType::TRANSFER_OUT->value,
                    'reason' => 'Transferência de saída',
                    'source_type' => 'transfer',
                    'warehouse_id' => $transfer->origin_warehouse_id,
                    'quantity' => (float) $item->quantity,
                    'document_number' => $transfer->code,
                    'observation' => "Transferência {$transfer->code} | item #{$item->id}",
                ]);
            }

            $transfer->status = TransferStatus::IN_TRANSIT->value;
            $transfer->sender_id = auth()->id();
            $transfer->shipped_at = now();
            $transfer->saveQuietly();

            return $transfer->load('items.product');
        });
    }

    /**
     * Recebimento: credita o destino (TRANSFER_IN) pela quantidade recebida
     * (default = total; suporta parcial). Fecha a transferência.
     */
    public function receive(int $id, ?array $received = null): Transfer
    {
        return DB::transaction(function () use ($id, $received) {
            $transfer = $this->requireStatus($id, TransferStatus::IN_TRANSIT);

            foreach ($transfer->items as $item) {
                $qty = $received["item_{$item->id}"] ?? $received[$item->id] ?? (float) $item->quantity;
                $qty = min((float) $qty, (float) $item->quantity);
                $item->quantity_received = max(0, $qty);
                $item->saveQuietly();

                $this->stock->apply([
                    'product_id' => $item->product_id,
                    'type' => MovementType::TRANSFER_IN->value,
                    'reason' => 'Transferência de entrada',
                    'source_type' => 'transfer',
                    'warehouse_id' => $transfer->destination_warehouse_id,
                    'quantity' => (float) $item->quantity_received,
                    'document_number' => $transfer->code,
                    'observation' => "Transferência {$transfer->code} | item #{$item->id}",
                ]);
            }

            $transfer->status = TransferStatus::RECEIVED->value;
            $transfer->receiver_id = auth()->id();
            $transfer->received_at = now();
            $transfer->saveQuietly();

            return $transfer->load('items.product');
        });
    }

    /** Cancela (apenas a partir de pending). Não reverte estoque embarcado. */
    public function cancel(int $id): Transfer
    {
        return DB::transaction(function () use ($id) {
            $transfer = $this->requireStatus($id, TransferStatus::PENDING);
            $transfer->status = TransferStatus::CANCELLED->value;
            $transfer->saveQuietly();

            return $transfer->fresh();
        });
    }

    protected function requireStatus(int $id, TransferStatus $expected): Transfer
    {
        $transfer = Transfer::with('items.product')->findOrFail($id);
        if ($transfer->status !== $expected->value) {
            throw ValidationException::withMessages([
                'status' => ["Transição inválida: estado atual '{$transfer->status}', esperado '{$expected->value}'."],
            ]);
        }

        return $transfer;
    }

    protected function assertAvailable(Product $product, float $qty, int $warehouseId): void
    {
        $balance = $product->stockBalances()->where('warehouse_id', $warehouseId)->first();
        $available = $balance?->available ?? $product->available_stock;
        if ((float) $available < $qty) {
            throw ValidationException::withMessages([
                'stock' => ["Estoque insuficiente em origem para '{$product->name}': disponível {$available}, necessário {$qty}."],
            ]);
        }
    }

    /**
     * @param  TransferItemDto[]  $items
     */
    protected function syncItems(Transfer $transfer, array $items): void
    {
        $transfer->items()->delete();
        foreach ($items as $item) {
            $transfer->items()->create($item->toArray());
        }
    }
}
