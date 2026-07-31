<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Saída de Lote (com alerta de validade). */
class LotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lot_number' => $this->lot_number,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'warehouse' => $this->whenLoaded('warehouse'),
            'supplier' => $this->whenLoaded('supplier'),
            'quantity' => $this->quantity,
            'remaining' => $this->remaining,
            'manufactured_at' => $this->manufactured_at,
            'expires_at' => $this->expires_at,
            'days_to_expiry' => $this->daysToExpiry(),
            'is_expired' => $this->isExpired(),
            'is_near_expiry' => $this->isNearExpiry(),
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
