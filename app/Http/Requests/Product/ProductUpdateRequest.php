<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('product');

        return [
            'internal_code' => ['sometimes', 'required', 'string', 'max:30', "unique:products,internal_code,{$id}"],
            'barcode' => ['nullable', 'string', 'max:50'],
            'qrcode' => ['nullable', 'string', 'max:100'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'manufacturer_id' => ['nullable', 'exists:manufacturers,id'],
            'model' => ['nullable', 'string', 'max:100'],
            'unit_id' => ['sometimes', 'required', 'exists:units,id'],
            'min_stock' => ['sometimes', 'numeric', 'min:0'],
            'max_stock' => ['sometimes', 'numeric', 'min:0'],
            'last_cost' => ['sometimes', 'numeric', 'min:0'],
            'average_cost' => ['sometimes', 'numeric', 'min:0'],
            'sale_price' => ['sometimes', 'numeric', 'min:0'],
            'ncm' => ['nullable', 'string', 'max:10'],
            'cfop' => ['nullable', 'string', 'max:4'],
            'cst' => ['nullable', 'string', 'max:4'],
            'control_batch' => ['boolean'],
            'control_expiry' => ['boolean'],
            'serialized' => ['boolean'],
            'active' => ['boolean'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'aisle' => ['nullable', 'string', 'max:20'],
            'corridor' => ['nullable', 'string', 'max:20'],
            'shelf' => ['nullable', 'string', 'max:20'],
            'level' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:20'],
            'image' => ['nullable', 'string', 'max:255'],
        ];
    }
}
