<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'material_request_id', 'product_id', 'quantity_requested',
    'quantity_approved', 'quantity_delivered', 'warehouse_id', 'observation',
])]
class MaterialRequestItem extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $auditableFields = [
        'material_request_id', 'product_id', 'quantity_requested',
        'quantity_approved', 'quantity_delivered', 'warehouse_id', 'observation',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:4',
            'quantity_approved' => 'decimal:4',
            'quantity_delivered' => 'decimal:4',
        ];
    }

    public function materialRequest(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Quantidade ainda pendente de entrega deste item.
     */
    public function pending(): float
    {
        return max(0, (float) $this->quantity_approved - (float) $this->quantity_delivered);
    }
}
