<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Saída de Solicitação de Materiais (cabeçalho + itens). */
class MaterialRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'status_label' => $this->statusEnum()->label(),
            'justification' => $this->justification,
            'observation' => $this->observation,
            'approved_at' => $this->approved_at,
            'delivered_at' => $this->delivered_at,
            'finished_at' => $this->finished_at,
            'requester' => $this->whenLoaded('requester', fn () => ['id' => $this->requester?->id, 'name' => $this->requester?->name]),
            'approver' => $this->whenLoaded('approver', fn () => ['id' => $this->approver?->id, 'name' => $this->approver?->name]),
            'employee' => $this->whenLoaded('employee'),
            'sector' => $this->whenLoaded('sector'),
            'cost_center' => $this->whenLoaded('costCenter'),
            'items' => MaterialRequestItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
