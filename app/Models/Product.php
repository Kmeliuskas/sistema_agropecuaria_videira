<?php

namespace App\Models;

use App\Traits\Auditable;
use HotwiredLaravel\TurboLaravel\Facades\TurboStream;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;
use Illuminate\Broadcasting\PrivateChannel;

#[Fillable([
    'internal_code', 'barcode', 'qrcode', 'name', 'description',
    'category_id', 'subcategory_id', 'brand_id', 'manufacturer_id', 'model', 'unit_id',
    'min_stock', 'max_stock', 'current_stock', 'reserved_stock', 'available_stock',
    'last_cost', 'average_cost', 'sale_price',
    'ncm', 'cfop', 'cst',
    'control_batch', 'control_expiry', 'serialized', 'active',
    'warehouse_id', 'aisle', 'corridor', 'shelf', 'level', 'position', 'image',
])]
class Product extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $auditableFields = [
        'internal_code', 'barcode', 'name', 'description', 'category_id',
        'subcategory_id', 'brand_id', 'manufacturer_id', 'model', 'unit_id',
        'min_stock', 'max_stock', 'last_cost', 'average_cost', 'sale_price',
        'ncm', 'cfop', 'cst', 'control_batch', 'control_expiry', 'serialized',
        'active', 'warehouse_id', 'aisle', 'corridor', 'shelf', 'level', 'position',
    ];

    protected function casts(): array
    {
        return [
            'min_stock' => 'decimal:4',
            'max_stock' => 'decimal:4',
            'current_stock' => 'decimal:4',
            'reserved_stock' => 'decimal:4',
            'available_stock' => 'decimal:4',
            'last_cost' => 'decimal:4',
            'average_cost' => 'decimal:4',
            'sale_price' => 'decimal:4',
            'control_batch' => 'boolean',
            'control_expiry' => 'boolean',
            'serialized' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ProductLocation::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProductAttachment::class);
    }

    /**
     * Saldo disponível = atual - reservado (regra de negócio central).
     */
    public function recalcAvailable(): void
    {
        $this->available_stock = max(0, $this->current_stock - $this->reserved_stock);
        $this->saveQuietly();
    }

    public function isBelowMin(): bool
    {
        return $this->current_stock < $this->min_stock;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Garante que available_stock seja sempre derivado de current - reserved
     * antes de persistir (regra de negócio única de verdade).
     */
    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            $product->available_stock = max(0, $product->current_stock - $product->reserved_stock);
        });

        static::updated(function (Product $product) {
            if ($product->wasChanged('current_stock') && $product->warehouse_id) {
                $sb = StockBalance::firstOrNew([
                    'product_id' => $product->id,
                    'warehouse_id' => $product->warehouse_id,
                ]);
                $sb->current = $product->current_stock;
                $sb->save();
                $sb->recalcAvailable();
            }
            $product->broadcastRowUpdate();
        });

        static::created(function (Product $product) {
            $product->loadMissing(['category', 'brand', 'unit', 'warehouse']);
            $domId = dom_id($product);
            $content = view('products._row_broadcast', ['product' => $product])->render();

            TurboStream::broadcastPrepend(
                target: 'products_table_body',
                content: new HtmlString($content),
                channel: new PrivateChannel('products'),
            );
        });

        static::deleted(function (Product $product) {
            $domId = dom_id($product);

            TurboStream::broadcastRemove(
                target: $domId,
                channel: new PrivateChannel('products'),
            );
            TurboStream::broadcastRemove(
                target: $domId,
                channel: new PrivateChannel('stock.' . $product->id),
            );
        });
    }

    /**
     * Tempo real: dispara um Turbo Stream "replace" para o canal privado do produto.
     */
    public function broadcastStockUpdate(): void
    {
        $this->broadcastRowUpdate();
    }

    /**
     * Tempo real: dispara a <tr> inteira da listagem de produtos.
     * Usado por views que precisam refletir mudanças em nome/categoria/estoque/etc.
     */
    public function broadcastRowUpdate(): void
    {
        $this->broadcastRow();
    }

    /**
     * Renderiza a TR da listagem para o broadcast.
     */
    protected function broadcastRow(): void
    {
        $this->loadMissing(['category', 'brand', 'unit', 'warehouse']);
        $domId = dom_id($this);
        $content = view('products._row_broadcast', ['product' => $this])->render();

        TurboStream::broadcastAction(
            action: 'replace',
            content: new HtmlString($content),
            target: $domId,
            channel: new PrivateChannel('stock.' . $this->id),
        );

        TurboStream::broadcastAction(
            action: 'replace',
            content: new HtmlString($content),
            target: $domId,
            channel: new PrivateChannel('products'),
        );
    }
}
