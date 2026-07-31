<?php

namespace App\Application\DTOs\MaterialRequest;

use App\Application\DTOs\Dto;

/** DTO de item de Solicitação de Materiais. */
class MaterialRequestItemDto extends Dto
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $productId,
        public readonly float $quantityRequested,
        public readonly float $quantityApproved,
        public readonly float $quantityDelivered,
        public readonly ?int $warehouseId,
        public readonly ?string $observation,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['id'] ?? null,
            productId: $data['product_id'],
            quantityRequested: (float) ($data['quantity_requested'] ?? 0),
            quantityApproved: (float) ($data['quantity_approved'] ?? 0),
            quantityDelivered: (float) ($data['quantity_delivered'] ?? 0),
            warehouseId: $data['warehouse_id'] ?? null,
            observation: $data['observation'] ?? null,
        );
    }

    public function toArray(): array
    {
        return $this->compact([
            'product_id' => $this->productId,
            'quantity_requested' => $this->quantityRequested,
            'quantity_approved' => $this->quantityApproved,
            'quantity_delivered' => $this->quantityDelivered,
            'warehouse_id' => $this->warehouseId,
            'observation' => $this->observation,
        ]);
    }
}
