<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Saída de Ajuste de estoque (6 motivos). */
class AdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'warehouse' => $this->whenLoaded('warehouse', fn () => new WarehouseResource($this->warehouse)),
            'reason' => $this->reason,
            'reason_label' => $this->reasonEnum()->label(),
            'quantity' => $this->quantity,
            'is_loss' => $this->isLoss(),
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'user' => $this->whenLoaded('user', fn () => ['id' => $this->user?->id, 'name' => $this->user?->name]),
            'movement' => $this->whenLoaded('movement'),
            'observation' => $this->observation,
            'occurred_at' => $this->occurred_at,
            'created_at' => $this->created_at,
        ];
    }
}
