<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockBalanceResource;
use App\Models\StockBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

/** Consulta de saldos por produto x almoxarifado (os 6 saldos). */
class StockBalanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        $results = QueryBuilder::for(StockBalance::query())
            ->allowedFilters(['product_id', 'warehouse_id'])
            ->allowedSorts(['current', 'available', 'reserved'])
            ->allowedIncludes(['product', 'warehouse'])
            ->defaultSort('-current')
            ->paginate($perPage)
            ->withQueryString();

        return StockBalanceResource::collection($results)->response();
    }

    public function show(int $id): JsonResponse
    {
        $balance = StockBalance::with(['product', 'warehouse'])->findOrFail($id);

        return (new StockBalanceResource($balance))->response();
    }
}
