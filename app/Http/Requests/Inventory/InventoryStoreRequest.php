<?php

namespace App\Http\Requests\Inventory;

use App\Domain\Enums\InventoryType;
use App\Models\Inventory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Inventory::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(InventoryType::class)],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'category_id' => ['nullable', 'required_if:type,by_category', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'responsible_id' => ['nullable', 'exists:users,id'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'exists:products,id'],
            'items.*.warehouse_id' => ['required_with:items', 'exists:warehouses,id'],
            'items.*.book_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.counted_quantity' => ['nullable', 'numeric'],
        ];
    }
}
