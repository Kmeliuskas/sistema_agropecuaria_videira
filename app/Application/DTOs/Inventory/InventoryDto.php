<?php

namespace App\Application\DTOs\Inventory;

use App\Application\DTOs\Dto;

/**
 * DTO de Inventário (cabeçalho + itens a contar).
 * A contagem em si é enviada via InventoryItemDto no apontamento.
 */
class InventoryDto extends Dto
{
    /**
     * @param  InventoryItemDto[]|null  $items
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $code,
        public readonly string $type,
        public readonly ?int $warehouseId,
        public readonly ?int $categoryId,
        public readonly ?string $description,
        public readonly string $status,
        public readonly ?int $responsibleId,
        public readonly ?array $items,
    ) {}

    public static function fromArray(array $data): static
    {
        $items = null;
        if (isset($data['items']) && is_array($data['items'])) {
            $items = array_map(
                fn (array $i) => InventoryItemDto::fromArray($i),
                $data['items']
            );
        }

        return new self(
            id: $data['id'] ?? null,
            code: $data['code'] ?? null,
            type: $data['type'],
            warehouseId: $data['warehouse_id'] ?? null,
            categoryId: $data['category_id'] ?? null,
            description: $data['description'] ?? null,
            status: $data['status'] ?? 'draft',
            responsibleId: $data['responsible_id'] ?? null,
            items: $items,
        );
    }

    public function toArray(): array
    {
        return $this->compact([
            'code' => $this->code,
            'type' => $this->type,
            'warehouse_id' => $this->warehouseId,
            'category_id' => $this->categoryId,
            'description' => $this->description,
            'status' => $this->status,
            'responsible_id' => $this->responsibleId,
        ]);
    }
}
