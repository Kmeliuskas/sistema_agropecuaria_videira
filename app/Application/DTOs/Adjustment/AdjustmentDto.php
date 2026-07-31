<?php

namespace App\Application\DTOs\Adjustment;

use App\Application\DTOs\Dto;
use App\Domain\Enums\AdjustmentReason;

/**
 * DTO de Ajuste de estoque (6 motivos).
 * quantity pode ser positiva (ganho) ou negativa (perda).
 */
class AdjustmentDto extends Dto
{
    public function __construct(
        public readonly ?string $code,
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly string $reason,
        public readonly float $quantity,
        public readonly ?int $userId,
        public readonly ?string $observation,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            code: $data['code'] ?? null,
            productId: (int) $data['product_id'],
            warehouseId: (int) $data['warehouse_id'],
            reason: $data['reason'],
            quantity: (float) $data['quantity'],
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            observation: $data['observation'] ?? null,
        );
    }

    public function reasonEnum(): AdjustmentReason
    {
        return AdjustmentReason::from($this->reason);
    }

    public function toArray(): array
    {
        return $this->compact([
            'code' => $this->code,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'reason' => $this->reason,
            'quantity' => $this->quantity,
            'user_id' => $this->userId,
            'observation' => $this->observation,
        ]);
    }
}
