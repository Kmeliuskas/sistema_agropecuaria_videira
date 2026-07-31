<?php

namespace App\Infrastructure\Repositories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;

class WarehouseRepository extends EloquentRepository
{
    protected array $searchable = ['name', 'code', 'city'];

    protected array $filterable = ['warehouse_type_id', 'is_active', 'is_default'];

    protected array $allowedIncludes = ['stockBalances'];

    protected array $sortable = ['id', 'name', 'code', 'created_at'];

    protected function model(): Model
    {
        return new Warehouse;
    }
}
