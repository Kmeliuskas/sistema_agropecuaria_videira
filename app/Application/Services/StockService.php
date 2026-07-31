<?php

namespace App\Application\Services;

use App\Domain\Enums\MovementType;
use App\Models\Movement;
use App\Models\Product;
use App\Models\StockBalance;
use Illuminate\Support\Facades\DB;

/**
 * Serviço de domínio do estoque. Regras de negócio centrais:
 * - atualiza saldo consolidado (products.current_stock) e saldo por almoxarifado
 *   (stock_balances) de forma atômica;
 * - registra o Kardex (movements) com saldo antes/depois;
 * - disponível = atual - reservado (regra única de verdade).
 *
 * Deve ser usado por entradas, saídas, ajustes e transferências.
 */
class StockService
{
    /**
     * Aplica uma movimentação e retorna o model Movement criado.
     *
     * @param  array  $data  campos do Movement + 'product_id'
     */
    public function apply(array $data): Movement
    {
        return DB::transaction(function () use ($data) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            $type = MovementType::from($data['type']);
            $warehouseId = $data['warehouse_id'] ?? $product->warehouse_id;

            $balanceBefore = $product->current_stock;
            $delta = $this->delta($type, (float) $data['quantity']);
            $balanceAfter = max(0, $balanceBefore + $delta);

            // Kardex
            $movement = Movement::create(array_merge($data, [
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'warehouse_id' => $warehouseId,
                'user_id' => auth()->id(),
                'occurred_at' => $data['occurred_at'] ?? now(),
            ]));

            // Saldo consolidado + custo médio (entradas)
            $product->current_stock = $balanceAfter;
            if ($type->isEntrance() && ($data['unit_cost'] ?? 0) > 0) {
                $product->last_cost = $data['unit_cost'];
                $product->average_cost = $this->recalcAverageCost($product, $delta, (float) $data['unit_cost']);
            }
            $product->saveQuietly();
            $product->recalcAvailable();

            // Saldo por almoxarifado
            $this->applyBalance($product->id, $warehouseId, $delta);
            if ($type === MovementType::TRANSFER_OUT && ! empty($data['warehouse_destination_id'])) {
                $this->applyBalance($product->id, $data['warehouse_destination_id'], abs($delta));
            }

            return $movement;
        });
    }

    protected function delta(MovementType $type, float $qty): float
    {
        return match ($type->direction()) {
            'in' => abs($qty),
            'out' => -abs($qty),
            'neutral' => $qty, // adjust: sinal do próprio valor
        };
    }

    protected function applyBalance(int $productId, int $warehouseId, float $delta): StockBalance
    {
        $balance = StockBalance::firstOrNew([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ]);
        $balance->current = max(0, ($balance->current ?? 0) + $delta);
        $balance->saveQuietly();
        $balance->recalcAvailable();

        return $balance;
    }

    protected function recalcAverageCost(Product $product, float $deltaIn, float $unitCost): float
    {
        if ($deltaIn <= 0) {
            return (float) $product->average_cost;
        }
        $totalQty = $product->current_stock; // já atualizado
        $totalValue = ($totalQty - $deltaIn) * ($product->average_cost ?: 0) + $deltaIn * $unitCost;

        return $totalQty > 0 ? round($totalValue / $totalQty, 4) : $unitCost;
    }
}
