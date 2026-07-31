<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'internal_code' => ['required', 'string', 'max:30', 'unique:products,internal_code'],
            'barcode' => ['nullable', 'string', 'max:50'],
            'qrcode' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'manufacturer_id' => ['nullable', 'exists:manufacturers,id'],
            'model' => ['nullable', 'string', 'max:100'],
            'unit_id' => ['required', 'exists:units,id'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'max_stock' => ['required', 'numeric', 'min:0'],
            'last_cost' => ['required', 'numeric', 'min:0'],
            'average_cost' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
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

    public function bodyParameters()
    {
        return [
            'internal_code' => ['description' => 'Código interno único do produto.', 'example' => 'PROD-0001'],
            'name' => ['description' => 'Nome do produto.', 'example' => 'Parafuso Hexagonal 10mm'],
        ];
    }
}
