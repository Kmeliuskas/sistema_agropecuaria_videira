<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Saldo por produto x almoxarifado (6 saldos). */
class StockBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'warehouse' => $this->whenLoaded('warehouse'),
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'current' => $this->current,
            'reserved' => $this->reserved,
            'available' => $this->available,
            'blocked' => $this->blocked,
            'in_conferencia' => $this->in_conferencia,
            'in_transit' => $this->in_transit,
        ];
    }
}
