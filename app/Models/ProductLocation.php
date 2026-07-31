<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'product_id', 'warehouse_id', 'aisle', 'corridor', 'shelf', 'level', 'position',
    'quantity', 'is_primary',
])]
class ProductLocation extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'is_primary' => 'boolean',
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

    /**
     * Formata a localização completa (rua/corredor/prateleira/nível/posição).
     */
    public function getFullLocationAttribute(): string
    {
        $parts = array_filter([
            $this->aisle,
            $this->corridor,
            $this->shelf,
            $this->level,
            $this->position,
        ]);

        return $parts ? implode(' / ', $parts) : '—';
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function scopeByWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }
}
