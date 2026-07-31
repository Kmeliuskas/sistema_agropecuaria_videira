<?php

namespace App\Models;

use App\Domain\Enums\MovementType;
use App\Traits\Auditable;
use HotwiredLaravel\TurboLaravel\Facades\TurboStream;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;

#[Fillable([
    'product_id', 'type', 'reason', 'source_type',
    'warehouse_id', 'warehouse_destination_id', 'quantity', 'unit_cost',
    'balance_before', 'balance_after', 'user_id', 'employee_id', 'cost_center_id',
    'supplier_id', 'document_number', 'observation', 'occurred_at',
])]
class Movement extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $auditableFields = [
        'product_id', 'type', 'reason', 'source_type', 'warehouse_id',
        'warehouse_destination_id', 'quantity', 'unit_cost', 'balance_before',
        'balance_after', 'user_id', 'employee_id', 'cost_center_id', 'supplier_id',
        'document_number', 'observation',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
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
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function warehouseDestination(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_destination_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function typeEnum(): MovementType
    {
        return MovementType::from($this->type);
    }

    public function scopeOfType(Builder $query, MovementType|string $type): Builder
    {
        return $query->where('type', is_string($type) ? $type : $type->value);
    }

    protected static function booted(): void
    {
        static::created(function (Movement $m) {
            $m->loadMissing(['product', 'warehouse', 'user']);
            $content = view('movements._row', ['m' => $m])->render();
            TurboStream::broadcastPrepend(
                target: 'movements_table_body',
                content: new HtmlString($content),
                channel: new PrivateChannel('movements'),
            );
        });
    }
}
