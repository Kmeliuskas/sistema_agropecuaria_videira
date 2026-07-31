<?php

namespace App\Infrastructure\Repositories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Model;

class BrandRepository extends EloquentRepository
{
    protected array $searchable = ['name', 'code'];

    protected array $filterable = ['is_active'];

    protected array $sortable = ['id', 'name', 'code'];

    protected function model(): Model
    {
        return new Brand;
    }
}
