<?php

namespace App\Models;

use App\Domain\Enums\AdjustmentReason;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code', 'product_id', 'warehouse_id', 'reason', 'quantity',
    'balance_before', 'balance_after', 'user_id', 'observation',
    'movement_id', 'occurred_at',
])]
class Adjustment extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $auditableFields = [
        'code', 'product_id', 'warehouse_id', 'reason', 'quantity',
        'balance_before', 'balance_after', 'user_id', 'observation',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'balance_before' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'occurred_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    public function reasonEnum(): AdjustmentReason
    {
        return AdjustmentReason::from($this->reason);
    }

    /**
     * Ajuste de perda (quantidade negativa no Kardex).
     */
    public function isLoss(): bool
    {
        return (float) $this->quantity < 0;
    }
}
