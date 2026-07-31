<?php

namespace App\Infrastructure\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class ProductRepository extends EloquentRepository
{
    protected array $searchable = ['name', 'internal_code', 'barcode', 'description'];

    protected array $filterable = ['category_id', 'subcategory_id', 'brand_id', 'unit_id', 'active', 'warehouse_id'];

    protected array $allowedIncludes = ['category', 'subcategory', 'brand', 'unit', 'warehouse', 'stockBalances'];

    protected array $sortable = ['id', 'name', 'internal_code', 'current_stock', 'sale_price', 'created_at'];

    protected function model(): Model
    {
        return new Product;
    }
}
