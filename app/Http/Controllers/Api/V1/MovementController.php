<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\StockService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Movement\MovementRequest;
use App\Http\Resources\MovementResource;
use App\Models\Movement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Endpoint de movimentações (Kardex). Entradas, saídas, ajustes e transferências
 * passam por aqui; o StockService aplica o saldo e registra o histórico.
 */
class MovementController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        $results = QueryBuilder::for(Movement::query())
            ->allowedFilters([
                'type', 'reason', 'source_type',
                'warehouse_id', 'product_id', 'cost_center_id',
                'user_id', 'supplier_id',
            ])
            ->allowedSorts(['id', 'occurred_at', 'created_at'])
            ->allowedIncludes(['product', 'warehouse', 'user', 'costCenter', 'supplier'])
            ->defaultSort('-occurred_at')
            ->paginate($perPage)
            ->withQueryString();

        return MovementResource::collection($results)->response();
    }

    public function show(int $id): JsonResponse
    {
        $movement = Movement::with(['product', 'warehouse', 'user'])->findOrFail($id);

        return (new MovementResource($movement))->response();
    }

    public function store(MovementRequest $request): JsonResponse
    {
        $movement = $this->stock->apply($request->validated());

        return (new MovementResource($movement->load(['product', 'warehouse', 'user'])))
            ->response()
            ->setStatusCode(201);
    }
}
