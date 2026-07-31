<?php

namespace App\Models;

use App\Domain\Enums\InventoryStatus;
use App\Domain\Enums\InventoryType;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code', 'type', 'warehouse_id', 'category_id', 'description',
    'status', 'responsible_id', 'started_at', 'finalized_at',
    'items_count', 'counted_count',
])]
class Inventory extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $auditableFields = [
        'code', 'type', 'warehouse_id', 'category_id', 'description',
        'status', 'responsible_id', 'items_count', 'counted_count',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function typeEnum(): InventoryType
    {
        return InventoryType::from($this->type);
    }

    public function statusEnum(): InventoryStatus
    {
        return InventoryStatus::from($this->status);
    }

    /**
     * Progresso da contagem (0..1) para dashboards.
     */
    public function progress(): float
    {
        if ($this->items_count <= 0) {
            return 0.0;
        }

        return round($this->counted_count / $this->items_count, 4);
    }
}
