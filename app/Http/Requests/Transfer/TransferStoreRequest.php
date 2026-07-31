<?php

namespace App\Http\Requests\Transfer;

use App\Models\Transfer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TransferStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Transfer::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'origin_warehouse_id' => ['required', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['required', 'exists:warehouses,id', 'different:origin_warehouse_id'],
            'requester_id' => ['nullable', 'exists:users,id'],
            'observation' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.observation' => ['nullable', 'string'],
        ];
    }
}
