<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Saída de item de Inventário (book x counted -> diferença). */
class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'warehouse' => $this->whenLoaded('warehouse', fn () => new WarehouseResource($this->warehouse)),
            'book_quantity' => $this->book_quantity,
            'counted_quantity' => $this->counted_quantity,
            'difference' => $this->difference,
            'is_counted' => $this->is_counted,
            'has_difference' => $this->hasDifference(),
            'counter' => $this->whenLoaded('counter', fn () => ['id' => $this->counter?->id, 'name' => $this->counter?->name]),
            'counted_at' => $this->counted_at,
        ];
    }
}
