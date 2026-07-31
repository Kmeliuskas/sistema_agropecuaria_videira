<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Saída de Transferência (cabeçalho + itens). */
class TransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'status_label' => $this->statusEnum()->label(),
            'observation' => $this->observation,
            'shipped_at' => $this->shipped_at,
            'received_at' => $this->received_at,
            'origin_warehouse' => $this->whenLoaded('originWarehouse', fn () => new WarehouseResource($this->originWarehouse)),
            'destination_warehouse' => $this->whenLoaded('destinationWarehouse', fn () => new WarehouseResource($this->destinationWarehouse)),
            'requester' => $this->whenLoaded('requester', fn () => ['id' => $this->requester?->id, 'name' => $this->requester?->name]),
            'sender' => $this->whenLoaded('sender', fn () => ['id' => $this->sender?->id, 'name' => $this->sender?->name]),
            'receiver' => $this->whenLoaded('receiver', fn () => ['id' => $this->receiver?->id, 'name' => $this->receiver?->name]),
            'items' => TransferItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
