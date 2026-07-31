<?php

namespace App\Application\Services;

use App\Application\DTOs\Adjustment\AdjustmentDto;
use App\Domain\Enums\MovementType;
use App\Models\Adjustment;
use App\Models\Movement;
use App\Models\Product;
use App\Models\StockBalance;
use Illuminate\Support\Facades\DB;

/**
 * Caso de uso de Ajuste de estoque (6 motivos: erro, quebra, perda, roubo,
 * vencimento, correção).
 *
 * Regra central: um ajuste é uma correção pontual. Aplica MovementType::ADJUST
 * via StockService (direção 'neutral' -> usa o sinal do próprio valor: positivo
 * acrescenta, negativo consome). Registra balance_before/after no próprio
 * model para auditoria financeira, e vincula o Movement gerado.
 */
class AdjustmentService
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    public function create(AdjustmentDto $dto): Adjustment
    {
        return DB::transaction(function () use ($dto) {
            $product = Product::lockForUpdate()->findOrFail($dto->productId);
            $balance = StockBalance::where('product_id', $dto->productId)
                ->where('warehouse_id', $dto->warehouseId)
                ->first();

            $balanceBefore = $balance?->current ?? $product->current_stock;
            $balanceAfter = max(0, (float) $balanceBefore + $dto->quantity);

            $data = $dto->toArray();
            if (empty($data['code'])) {
                $data['code'] = $this->generateCode();
            }
            $data['user_id'] ??= auth()->id();
            $data['balance_before'] = $balanceBefore;
            $data['balance_after'] = $balanceAfter;

            $adjustment = Adjustment::create($data);

            $movement = $this->stock->apply([
                'product_id' => $dto->productId,
                'type' => MovementType::ADJUST->value,
                'reason' => $dto->reasonEnum()->label(),
                'source_type' => 'adjustment',
                'warehouse_id' => $dto->warehouseId,
                'quantity' => $dto->quantity, // sinal define ganho/perda
                'document_number' => $adjustment->code,
                'observation' => $dto->observation ?? $dto->reasonEnum()->label(),
            ]);

            $adjustment->movement_id = $movement->id;
            $adjustment->occurred_at = now();
            $adjustment->saveQuietly();

            return $adjustment->load('product', 'warehouse', 'movement');
        });
    }

    protected function generateCode(): string
    {
        $prefix = 'AD-'.now()->format('Ymd');
        $last = Adjustment::where('code', 'like', "{$prefix}-%")
            ->orderByDesc('id')
            ->value('code');

        $seq = 1;
        if ($last) {
            $seq = (int) substr($last, strlen($prefix) + 1) + 1;
        }

        return "{$prefix}-".str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function find(int $id): Adjustment
    {
        return Adjustment::with(['product', 'warehouse', 'movement'])
            ->findOrFail($id);
    }

    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Adjustment::query();

        if (! empty($filters['reason'])) {
            $query->where('reason', $filters['reason']);
        }
        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
