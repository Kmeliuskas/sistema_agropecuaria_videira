<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Saída de Inventário (cabeçalho + itens). */
class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'type_label' => $this->typeEnum()->label(),
            'status' => $this->status,
            'status_label' => $this->statusEnum()->label(),
            'description' => $this->description,
            'progress' => $this->progress(),
            'items_count' => $this->items_count,
            'counted_count' => $this->counted_count,
            'started_at' => $this->started_at,
            'finalized_at' => $this->finalized_at,
            'warehouse' => $this->whenLoaded('warehouse'),
            'category' => $this->whenLoaded('category'),
            'responsible' => $this->whenLoaded('responsible', fn () => ['id' => $this->responsible?->id, 'name' => $this->responsible?->name]),
            'items' => InventoryItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
