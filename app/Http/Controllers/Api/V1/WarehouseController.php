<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseResource;
use App\Infrastructure\Repositories\WarehouseRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Almoxarifados. */
class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseRepository $repository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $results = $this->repository->list($request->query(), $perPage);

        return WarehouseResource::collection($results)->response();
    }

    public function show(int $id): JsonResponse
    {
        return (new WarehouseResource($this->repository->findOrFail($id)))->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'warehouse_type_id' => ['nullable', 'exists:warehouse_types,id'],
            'responsible' => ['nullable', 'string', 'max:100'],
            'document' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:2'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        $warehouse = $this->repository->create($data);

        return (new WarehouseResource($warehouse))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $warehouse): JsonResponse
    {
        $warehouse = $this->repository->findOrFail($warehouse);
        $data = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:20', "unique:warehouses,code,{$warehouse->id}"],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'warehouse_type_id' => ['nullable', 'exists:warehouse_types,id'],
            'responsible' => ['nullable', 'string', 'max:100'],
            'document' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:2'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        $this->repository->update($warehouse, $data);

        return (new WarehouseResource($warehouse->fresh()))->response();
    }

    public function destroy(Request $request): JsonResponse
    {
        $warehouse = $this->repository->findOrFail($request->route('warehouse'));
        $this->repository->delete($warehouse);

        return response()->json(['message' => 'Almoxarifado excluído.'], 200);
    }
}
