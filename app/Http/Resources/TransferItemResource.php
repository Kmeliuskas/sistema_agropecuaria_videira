<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Saída de item de Transferência. */
class TransferItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'quantity' => $this->quantity,
            'quantity_received' => $this->quantity_received,
            'pending' => $this->pending(),
            'observation' => $this->observation,
        ];
    }
}
