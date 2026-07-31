<?php

namespace App\Infrastructure\Repositories;

use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Model;

class SubcategoryRepository extends EloquentRepository
{
    protected array $searchable = ['name', 'code'];

    protected array $filterable = ['category_id', 'is_active'];

    protected array $sortable = ['id', 'name', 'code'];

    protected function model(): Model
    {
        return new Subcategory;
    }
}
