<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code', 'name', 'document', 'contact_name', 'email', 'phone',
    'address', 'city', 'state', 'rating', 'is_active',
])]
class Supplier extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'rating' => 'decimal:2'];
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
