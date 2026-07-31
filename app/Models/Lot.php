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
    'product_id', 'warehouse_id', 'lot_number', 'supplier_id',
    'quantity', 'remaining', 'manufactured_at', 'expires_at', 'status',
])]
class Lot extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $auditableFields = [
        'product_id', 'warehouse_id', 'lot_number', 'supplier_id',
        'quantity', 'remaining', 'manufactured_at', 'expires_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'remaining' => 'decimal:4',
            'manufactured_at' => 'date',
            'expires_at' => 'date',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Dias até o vencimento (negativo se vencido).
     */
    public function daysToExpiry(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return (int) now()->diffInDays($this->expires_at, false);
    }

    public function isExpired(): bool
    {
        $days = $this->daysToExpiry();

        return $days !== null && $days < 0;
    }

    /**
     * Faixa de alerta definida pela regra de negócio: 7 a 90 dias.
     */
    public function isNearExpiry(): bool
    {
        $days = $this->daysToExpiry();

        return $days !== null && $days >= 0 && $days <= 90;
    }

    public function scopeExpiringBetween(Builder $query, int $fromDays, int $toDays): Builder
    {
        return $query->whereNotNull('expires_at')
            ->whereBetween('expires_at', [
                now()->addDays($fromDays)->toDateString(),
                now()->addDays($toDays)->toDateString(),
            ]);
    }
}
