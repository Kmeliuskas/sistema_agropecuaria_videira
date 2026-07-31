<?php

namespace App\Application\DTOs\Transfer;

use App\Application\DTOs\Dto;

/** DTO de item de Transferência. */
class TransferItemDto extends Dto
{
    public function __construct(
        public readonly int $productId,
        public readonly float $quantity,
        public readonly ?float $quantityReceived,
        public readonly ?string $observation,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            productId: (int) $data['product_id'],
            quantity: (float) $data['quantity'],
            quantityReceived: isset($data['quantity_received']) ? (float) $data['quantity_received'] : null,
            observation: $data['observation'] ?? null,
        );
    }

    public function toArray(): array
    {
        return $this->compact([
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'quantity_received' => $this->quantityReceived,
            'observation' => $this->observation,
        ]);
    }
}
