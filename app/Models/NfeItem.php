<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nfe_id', 'product_id', 'warehouse_id', 'quantity', 'unit_value', 'total_value',
])]
class NfeItem extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'nfe_items';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_value' => 'decimal:4',
            'total_value' => 'decimal:4',
        ];
    }

    public function nfe(): BelongsTo
    {
        return $this->belongsTo(Nfe::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
