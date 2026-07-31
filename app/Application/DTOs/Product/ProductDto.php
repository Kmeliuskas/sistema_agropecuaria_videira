<?php

namespace App\Application\DTOs\Product;

use App\Application\DTOs\Dto;

/**
 * DTO de entrada/saída para o caso de uso de Produto.
 * Isola a camada de aplicação da requisição HTTP e da persistência.
 */
class ProductDto extends Dto
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $internalCode,
        public readonly string $name,
        public readonly ?string $barcode,
        public readonly ?string $qrcode,
        public readonly ?string $description,
        public readonly ?int $categoryId,
        public readonly ?int $subcategoryId,
        public readonly ?int $brandId,
        public readonly ?int $manufacturerId,
        public readonly ?string $model,
        public readonly int $unitId,
        public readonly float $minStock,
        public readonly float $maxStock,
        public readonly float $lastCost,
        public readonly float $averageCost,
        public readonly float $salePrice,
        public readonly ?string $ncm,
        public readonly ?string $cfop,
        public readonly ?string $cst,
        public readonly bool $controlBatch,
        public readonly bool $controlExpiry,
        public readonly bool $serialized,
        public readonly bool $active,
        public readonly ?int $warehouseId,
        public readonly ?string $aisle,
        public readonly ?string $corridor,
        public readonly ?string $shelf,
        public readonly ?string $level,
        public readonly ?string $position,
        public readonly ?string $image,
        public readonly array $attributeValues, // [attribute_id => value]
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['id'] ?? null,
            internalCode: $data['internal_code'],
            name: $data['name'],
            barcode: $data['barcode'] ?? null,
            qrcode: $data['qrcode'] ?? null,
            description: $data['description'] ?? null,
            categoryId: $data['category_id'] ?? null,
            subcategoryId: $data['subcategory_id'] ?? null,
            brandId: $data['brand_id'] ?? null,
            manufacturerId: $data['manufacturer_id'] ?? null,
            model: $data['model'] ?? null,
            unitId: $data['unit_id'],
            minStock: (float) ($data['min_stock'] ?? 0),
            maxStock: (float) ($data['max_stock'] ?? 0),
            lastCost: (float) ($data['last_cost'] ?? 0),
            averageCost: (float) ($data['average_cost'] ?? 0),
            salePrice: (float) ($data['sale_price'] ?? 0),
            ncm: $data['ncm'] ?? null,
            cfop: $data['cfop'] ?? null,
            cst: $data['cst'] ?? null,
            controlBatch: (bool) ($data['control_batch'] ?? false),
            controlExpiry: (bool) ($data['control_expiry'] ?? false),
            serialized: (bool) ($data['serialized'] ?? false),
            active: (bool) ($data['active'] ?? true),
            warehouseId: $data['warehouse_id'] ?? null,
            aisle: $data['aisle'] ?? null,
            corridor: $data['corridor'] ?? null,
            shelf: $data['shelf'] ?? null,
            level: $data['level'] ?? null,
            position: $data['position'] ?? null,
            image: $data['image'] ?? null,
            attributeValues: $data['attribute_values'] ?? [],
        );
    }

    public function toArray(): array
    {
        return $this->compact([
            'internal_code' => $this->internalCode,
            'name' => $this->name,
            'barcode' => $this->barcode,
            'qrcode' => $this->qrcode,
            'description' => $this->description,
            'category_id' => $this->categoryId,
            'subcategory_id' => $this->subcategoryId,
            'brand_id' => $this->brandId,
            'manufacturer_id' => $this->manufacturerId,
            'model' => $this->model,
            'unit_id' => $this->unitId,
            'min_stock' => $this->minStock,
            'max_stock' => $this->maxStock,
            'last_cost' => $this->lastCost,
            'average_cost' => $this->averageCost,
            'sale_price' => $this->salePrice,
            'ncm' => $this->ncm,
            'cfop' => $this->cfop,
            'cst' => $this->cst,
            'control_batch' => $this->controlBatch,
            'control_expiry' => $this->controlExpiry,
            'serialized' => $this->serialized,
            'active' => $this->active,
            'warehouse_id' => $this->warehouseId,
            'aisle' => $this->aisle,
            'corridor' => $this->corridor,
            'shelf' => $this->shelf,
            'level' => $this->level,
            'position' => $this->position,
            'image' => $this->image,
            'attribute_values' => $this->attributeValues,
        ]);
    }
}
