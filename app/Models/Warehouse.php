<?php

namespace App\Models;

use App\Traits\Auditable;
use HotwiredLaravel\TurboLaravel\Facades\TurboStream;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;


#[Fillable([
    'code', 'name', 'description', 'warehouse_type_id', 'responsible', 'document',
    'address', 'city', 'state', 'is_active', 'is_default',
])]
class Warehouse extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $auditableFields = ['code', 'name', 'warehouse_type_id', 'is_active', 'is_default'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function warehouseType(): BelongsTo
    {
        return $this->belongsTo(WarehouseType::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::updated(function (Warehouse $wh) {
            $wh->broadcastRow();
        });

        static::created(function (Warehouse $wh) {
            $content = view('warehouses._card_broadcast', ['wh' => $wh])->render();
            TurboStream::broadcastPrepend(
                target: 'warehouses_list',
                content: new HtmlString($content),
                channel: new PrivateChannel('warehouses'),
            );
        });

        static::deleted(function (Warehouse $wh) {
            TurboStream::broadcastRemove(
                target: dom_id($wh),
                channel: new PrivateChannel('warehouses'),
            );
            TurboStream::broadcastRemove(
                target: dom_id($wh),
                channel: new PrivateChannel('warehouse.' . $wh->id),
            );
        });
    }

    protected function broadcastRow(): void
    {
        $content = view('warehouses._card_broadcast', ['wh' => $this])->render();
        TurboStream::broadcastAction(
            action: 'replace',
            content: new HtmlString($content),
            target: dom_id($this),
            channel: new PrivateChannel('warehouse.' . $this->id),
        );
        TurboStream::broadcastAction(
            action: 'replace',
            content: new HtmlString($content),
            target: dom_id($this),
            channel: new PrivateChannel('warehouses'),
        );
    }
}

