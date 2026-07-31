<?php

namespace App\Infrastructure\Repositories;

use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Model;

class ManufacturerRepository extends EloquentRepository
{
    protected array $searchable = ['name', 'code'];

    protected array $filterable = ['is_active'];

    protected array $sortable = ['id', 'name', 'code'];

    protected function model(): Model
    {
        return new Manufacturer;
    }
}
