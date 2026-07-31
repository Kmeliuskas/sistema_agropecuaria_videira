<?php

namespace App\Infrastructure\Repositories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

class UnitRepository extends EloquentRepository
{
    protected array $searchable = ['name', 'code', 'symbol'];

    protected array $filterable = ['is_active'];

    protected array $sortable = ['id', 'name', 'code'];

    protected function model(): Model
    {
        return new Unit;
    }
}
