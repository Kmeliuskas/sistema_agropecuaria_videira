<?php

namespace App\Models;

use App\Traits\Auditable;
use HotwiredLaravel\TurboLaravel\Facades\TurboStream;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;

#[Fillable([
    'product_id', 'warehouse_id', 'current', 'reserved', 'available',
    'blocked', 'in_conferencia', 'in_transit',
])]
class StockBalance extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $auditableFields = [
        'product_id', 'warehouse_id', 'current', 'reserved', 'available',
        'blocked', 'in_conferencia', 'in_transit',
    ];

    protected function casts(): array
    {
        return [
            'current' => 'decimal:4',
            'reserved' => 'decimal:4',
            'available' => 'decimal:4',
            'blocked' => 'decimal:4',
            'in_conferencia' => 'decimal:4',
            'in_transit' => 'decimal:4',
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
     * Disponível = atual - reservado - bloqueado - em conferência - em trânsito.
     * Não pode ser negativo.
     */
    public function recalcAvailable(): void
    {
        $this->available = max(0, $this->current - $this->reserved - $this->blocked - $this->in_conferencia - $this->in_transit);
        $this->saveQuietly();
    }

    protected static function booted(): void
    {
        static::saved(function (StockBalance $sb) {
            $sb->loadMissing(['product.unit', 'warehouse']);
            $content = view('stock._row', ['sb' => $sb])->render();
            TurboStream::broadcastAction(
                action: 'replace',
                content: new HtmlString($content),
                target: dom_id($sb),
                channel: new PrivateChannel('stock-balances'),
            );
            TurboStream::broadcastAction(
                action: 'replace',
                content: new HtmlString($content),
                target: dom_id($sb),
                channel: new PrivateChannel('stock.' . $sb->product_id),
            );
        });

        static::created(function (StockBalance $sb) {
            $sb->loadMissing(['product.unit', 'warehouse']);
            $content = view('stock._row', ['sb' => $sb])->render();
            TurboStream::broadcastPrepend(
                target: 'stock_table_body',
                content: new HtmlString($content),
                channel: new PrivateChannel('stock-balances'),
            );
        });

        static::deleted(function (StockBalance $sb) {
            TurboStream::broadcastRemove(
                target: dom_id($sb),
                channel: new PrivateChannel('stock-balances'),
            );
        });
    }
}
