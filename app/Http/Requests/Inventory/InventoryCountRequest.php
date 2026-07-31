<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InventoryCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $inventory = Inventory::findOrFail($this->route('inventory'));
        $this->merge(['_inventory' => $inventory]);

        return $this->user()->can('execute', $inventory);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'counted_quantity' => ['required', 'numeric', 'min:0'],
        ];
    }
}
