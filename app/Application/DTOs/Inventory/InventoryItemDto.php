<?php

namespace App\Application\DTOs\Inventory;

use App\Application\DTOs\Dto;

/** DTO de apontamento de contagem de item de inventário. */
class InventoryItemDto extends Dto
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly float $bookQuantity,
        public readonly ?float $countedQuantity,
        public readonly ?int $counterId,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['id'] ?? null,
            productId: $data['product_id'],
            warehouseId: $data['warehouse_id'],
            bookQuantity: (float) ($data['book_quantity'] ?? 0),
            countedQuantity: isset($data['counted_quantity']) ? (float) $data['counted_quantity'] : null,
            counterId: $data['counter_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return $this->compact([
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'book_quantity' => $this->bookQuantity,
            'counted_quantity' => $this->countedQuantity,
            'counter_id' => $this->counterId,
        ]);
    }
}
