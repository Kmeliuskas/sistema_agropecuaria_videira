<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Saída normalizada de Produto (evita vazamento de colunas internas). */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'internal_code' => $this->internal_code,
            'barcode' => $this->barcode,
            'qrcode' => $this->qrcode,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'subcategory' => $this->whenLoaded('subcategory', fn () => new SubcategoryResource($this->subcategory)),
            'brand' => $this->whenLoaded('brand', fn () => new BrandResource($this->brand)),
            'manufacturer' => $this->whenLoaded('manufacturer'),
            'model' => $this->model,
            'unit' => $this->whenLoaded('unit', fn () => new UnitResource($this->unit)),
            'min_stock' => $this->min_stock,
            'max_stock' => $this->max_stock,
            'current_stock' => $this->current_stock,
            'reserved_stock' => $this->reserved_stock,
            'available_stock' => $this->available_stock,
            'last_cost' => $this->last_cost,
            'average_cost' => $this->average_cost,
            'sale_price' => $this->sale_price,
            'ncm' => $this->ncm,
            'cfop' => $this->cfop,
            'cst' => $this->cst,
            'control_batch' => $this->control_batch,
            'control_expiry' => $this->control_expiry,
            'serialized' => $this->serialized,
            'active' => $this->active,
            'warehouse' => $this->whenLoaded('warehouse'),
            'location' => [
                'aisle' => $this->aisle,
                'corridor' => $this->corridor,
                'shelf' => $this->shelf,
                'level' => $this->level,
                'position' => $this->position,
            ],
            'image' => $this->image,
            'attributes' => $this->whenLoaded('attributes', fn () => $this->attributes->map(fn ($attr) => [
                'id' => $attr->id,
                'name' => $attr->name,
                'slug' => $attr->slug,
                'type' => $attr->type,
                'value' => $attr->pivot?->value,
            ])),
            'is_below_min' => $this->isBelowMin(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
