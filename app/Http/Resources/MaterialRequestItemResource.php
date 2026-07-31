<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Saída de item de Solicitação de Materiais. */
class MaterialRequestItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'quantity_requested' => $this->quantity_requested,
            'quantity_approved' => $this->quantity_approved,
            'quantity_delivered' => $this->quantity_delivered,
            'pending' => $this->pending(),
            'warehouse' => $this->whenLoaded('warehouse'),
            'observation' => $this->observation,
        ];
    }
}
