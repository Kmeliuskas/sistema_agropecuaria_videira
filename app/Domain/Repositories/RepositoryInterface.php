<?php

namespace App\Domain\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Contrato base de repositório (Repository Pattern).
 * Desacopla a camada de aplicação da infraestrutura (Eloquent).
 * Cada agregado define sua interface estendendo esta.
 */
interface RepositoryInterface
{
    public function all(array $columns = ['*']): Collection;

    public function find(int|string $id, array $columns = ['*']): ?Model;

    public function findOrFail(int|string $id, array $columns = ['*']): Model;

    public function paginate(int $perPage = 15, array $columns = ['*']): Paginator;

    public function create(array $attributes): Model;

    public function update(Model $model, array $attributes): Model;

    public function delete(Model $model): bool;

    /**
     * Aplica filtros do spatie/laravel-query-builder (search/filter/sort/includes)
     * e retorna paginado. Centraliza a consulta para reuso em index/list.
     */
    public function list(array $filters = [], int $perPage = 15): Paginator;
}
