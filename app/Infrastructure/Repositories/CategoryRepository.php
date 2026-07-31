<?php

namespace App\Infrastructure\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;

class CategoryRepository extends EloquentRepository
{
    protected array $searchable = ['name', 'code'];

    protected array $filterable = ['is_active'];

    protected array $allowedIncludes = ['subcategories'];

    protected array $sortable = ['id', 'name', 'code'];

    protected function model(): Model
    {
        return new Category;
    }
}
