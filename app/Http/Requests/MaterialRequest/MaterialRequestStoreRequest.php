<?php

namespace App\Http\Requests\MaterialRequest;

use App\Models\MaterialRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MaterialRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MaterialRequest::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:30', 'unique:material_requests,code'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'sector_id' => ['nullable', 'exists:sectors,id'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'justification' => ['nullable', 'string'],
            'observation' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity_requested' => ['required', 'numeric', 'min:0.0001'],
            'items.*.warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'items.*.observation' => ['nullable', 'string'],
        ];
    }
}
