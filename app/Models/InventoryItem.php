<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'inventory_id', 'product_id', 'warehouse_id', 'book_quantity',
    'counted_quantity', 'difference', 'is_counted', 'counter_id', 'counted_at',
])]
class InventoryItem extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $auditableFields = [
        'inventory_id', 'product_id', 'warehouse_id', 'book_quantity',
        'counted_quantity', 'difference', 'is_counted', 'counter_id',
    ];

    protected function casts(): array
    {
        return [
            'book_quantity' => 'decimal:4',
            'counted_quantity' => 'decimal:4',
            'difference' => 'decimal:4',
            'is_counted' => 'boolean',
            'counted_at' => 'datetime',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counter_id');
    }

    public function hasDifference(): bool
    {
        return abs((float) $this->difference) > 0.0001;
    }
}
