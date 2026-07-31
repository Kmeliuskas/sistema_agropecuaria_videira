<?php

namespace App\Http\Requests\Movement;

use App\Domain\Enums\MovementType;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação de entrada/saída/ajuste/transferência. O campo 'type' restringe
 * aos MovementType válidos. 'quantity' > 0 (para adjust, sinal define ganho/perda).
 */
class MovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('move', Product::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', Rule::enum(MovementType::class)],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'warehouse_destination_id' => ['nullable', 'exists:warehouses,id', 'different:warehouse_id'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:100'],
            'source_type' => ['nullable', 'string', 'max:50'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'document_number' => ['nullable', 'string', 'max:50'],
            'observation' => ['nullable', 'string'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
