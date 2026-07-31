<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\DashboardService;
use App\Application\Services\LotService;
use App\Http\Controllers\Controller;
use App\Http\Resources\LotResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

/**
 * Controller do Dashboard. KPIs operacionais + alertas de validade de lotes.
 * Leituras agregadas apenas.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly LotService $lots,
    ) {}

    #[OAT\Get(
        path: '/api/v1/dashboard',
        summary: 'KPIs do painel (totais, estoque baixo, Curva ABC, pendências)',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OAT\Response(response: 200, description: 'Resumo agregado'),
            new OAT\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function summary(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->summary()]);
    }

    #[OAT\Get(
        path: '/api/v1/dashboard/lots/expiring-soon',
        summary: 'Lotes com validade entre 7 e 90 dias',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OAT\Response(response: 200, description: 'Lotes próximos do vencimento'),
            new OAT\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function expiringSoon(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $results = $this->lots->expiringSoon($perPage);

        return LotResource::collection($results)->response();
    }

    #[OAT\Get(
        path: '/api/v1/dashboard/lots/expired',
        summary: 'Lotes já vencidos',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OAT\Response(response: 200, description: 'Lotes vencidos'),
            new OAT\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function expired(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $results = $this->lots->expired($perPage);

        return LotResource::collection($results)->response();
    }
}
