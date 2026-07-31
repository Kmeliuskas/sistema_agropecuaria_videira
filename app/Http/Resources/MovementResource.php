<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Saída do Kardex (movimentação). */
class MovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'type' => $this->type,
            'type_label' => $this->typeEnum()->label(),
            'reason' => $this->reason,
            'source_type' => $this->source_type,
            'warehouse' => $this->whenLoaded('warehouse'),
            'warehouse_destination' => $this->whenLoaded('warehouseDestination'),
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'user' => $this->whenLoaded('user', fn () => ['id' => $this->user?->id, 'name' => $this->user?->name]),
            'employee' => $this->whenLoaded('employee'),
            'cost_center' => $this->whenLoaded('costCenter'),
            'supplier' => $this->whenLoaded('supplier'),
            'document_number' => $this->document_number,
            'observation' => $this->observation,
            'occurred_at' => $this->occurred_at,
            'created_at' => $this->created_at,
        ];
    }
}
