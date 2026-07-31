<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Implementação Eloquent genérica do RepositoryInterface.
 * Usa spatie/laravel-query-builder para filtros/ordenação/include declarativos.
 * Subclasses podem sobrescrever $searchable, $filterable e $sortable.
 */
abstract class EloquentRepository implements RepositoryInterface
{
    protected Model $model;

    /** Colunas pesquisáveis via ?filter[search]= (LIKE). */
    protected array $searchable = [];

    /** Filtros exatos permitidos (?filter[col]=val). */
    protected array $filterable = [];

    /** Relações permitidas para include (?include=rel). */
    protected array $allowedIncludes = [];

    /** Colunas ordenáveis (?sort=col / ?sort=-col). */
    protected array $sortable = ['id', 'created_at', 'updated_at'];

    public function __construct()
    {
        $this->model = $this->model();
    }

    abstract protected function model(): Model;

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->get($columns);
    }

    public function find(int|string $id, array $columns = ['*']): ?Model
    {
        return $this->model->newQuery()->find($id, $columns);
    }

    public function findOrFail(int|string $id, array $columns = ['*']): Model
    {
        return $this->model->newQuery()->findOrFail($id, $columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): Paginator
    {
        return $this->model->newQuery()->paginate($perPage, $columns);
    }

    public function create(array $attributes): Model
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model->fresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    public function list(array $filters = [], int $perPage = 15): Paginator
    {
        $query = QueryBuilder::for($this->model->newQuery())
            ->allowedFilters(...$this->allowedFiltersList())
            ->allowedSorts(...$this->sortable)
            ->allowedIncludes(...$this->allowedIncludes);

        return $query->paginate($perPage)->withQueryString();
    }

    protected function exactFilters(): array
    {
        return array_map(fn ($col) => AllowedFilter::exact($col), $this->filterable);
    }

    protected function searchFilters(): array
    {
        if (empty($this->searchable)) {
            return [];
        }

        return [
            AllowedFilter::callback('search', function ($query, $value) {
                $query->where(function ($q) use ($value) {
                    foreach ($this->searchable as $col) {
                        $q->orWhere($col, 'LIKE', "%{$value}%");
                    }
                });
            }),
        ];
    }

    /**
     * Lista achatada de filtros permitidos (exact + search) para o QueryBuilder.
     */
    protected function allowedFiltersList(): array
    {
        return array_merge($this->exactFilters(), $this->searchFilters());
    }
}
