<?php

namespace App\Http\Requests\Adjustment;

use App\Domain\Enums\AdjustmentReason;
use App\Models\Adjustment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Adjustment::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'reason' => ['required', Rule::enum(AdjustmentReason::class)],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'observation' => ['nullable', 'string'],
        ];
    }
}
