<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Repositories\RepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Controller genérico para catálogos simples (Unit, Category, Brand, etc.).
 * Centraliza CRUD padrão e reuso entre recursos de baixa complexidade de regra.
 * Cada rota passa seu próprio FormRequest de validação via route binding de classe.
 */
class CatalogController extends Controller
{
    /**
     * @param  class-string<JsonResource>  $resourceClass
     */
    public function __construct(
        private readonly RepositoryInterface $repository,
        private readonly string $resourceClass,
        private readonly array $rules,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $results = $this->repository->list($request->query(), $perPage);
        $resource = $this->resourceClass;

        return $resource::collection($results)->response();
    }

    public function show(int $id): JsonResponse
    {
        $model = $this->repository->findOrFail($id);
        $resource = $this->resourceClass;

        return (new $resource($model))->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules['store'] ?? $this->rules);
        $model = $this->repository->create($data);
        $resource = $this->resourceClass;

        return (new $resource($model))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = $this->repository->findOrFail($id);
        $data = $request->validate($this->rules['update'] ?? $this->rules);
        $this->repository->update($model, $data);
        $resource = $this->resourceClass;

        return (new $resource($model->fresh()))->response();
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $model = $this->repository->findOrFail($id);
        $this->repository->delete($model);

        return response()->json(['message' => 'Registro excluído.'], 200);
    }
}
