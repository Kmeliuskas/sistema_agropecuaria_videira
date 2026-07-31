<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'supplier_id', 'number', 'series', 'emission_date', 'receipt_date',
    'total_value', 'status', 'observation', 'user_id',
])]
class Nfe extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'nfes';

    protected function casts(): array
    {
        return [
            'emission_date' => 'date',
            'receipt_date' => 'date',
            'total_value' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(NfeItem::class);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pendente',
            'received' => 'Recebida',
            'canceled' => 'Cancelada',
            default => $this->status,
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'pending' => 'badge-warning',
            'received' => 'badge-success',
            'canceled' => 'badge-danger',
            default => 'badge-secondary',
        };
    }
}
