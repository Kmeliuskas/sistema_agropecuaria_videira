<?php

namespace App\Infrastructure\Repositories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;

class SupplierRepository extends EloquentRepository
{
    protected array $searchable = ['name', 'code', 'document'];

    protected array $filterable = ['is_active'];

    protected array $sortable = ['id', 'name', 'code'];

    protected function model(): Model
    {
        return new Supplier;
    }
}
