<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'warehouse_type_id' => $this->warehouse_type_id,
            'warehouse_type' => $this->whenLoaded('warehouseType', fn () => $this->warehouseType?->name),
            'responsible' => $this->responsible,
            'document' => $this->document,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'created_at' => $this->created_at,
        ];
    }
}
