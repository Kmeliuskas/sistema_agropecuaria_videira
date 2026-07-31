<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\DTOs\Adjustment\AdjustmentDto;
use App\Application\Services\AdjustmentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Adjustment\AdjustmentStoreRequest;
use App\Http\Resources\AdjustmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

/**
 * Controller de Ajuste de estoque (6 motivos).
 * Cria o ajuste e aplica a correção no Kardex (MovementType::ADJUST).
 */
class AdjustmentController extends Controller
{
    public function __construct(
        private readonly AdjustmentService $service,
    ) {}

    #[OAT\Get(path: '/api/v1/adjustments', summary: 'Listar ajustes', tags: ['Ajustes'], security: [['sanctum' => []]], parameters: [new OAT\Parameter(name: 'per_page', in: 'query', schema: new OAT\Schema(type: 'integer')), new OAT\Parameter(name: 'reason', in: 'query', schema: new OAT\Schema(type: 'string'))], responses: [new OAT\Response(response: 200, description: 'Lista paginada')])]
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $results = $this->service->list($request->query(), $perPage);

        return AdjustmentResource::collection($results)->response();
    }

    #[OAT\Get(path: '/api/v1/adjustments/{adjustment}', summary: 'Detalhar ajuste', tags: ['Ajustes'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Detalhe')])]
    public function show(int $id): JsonResponse
    {
        return (new AdjustmentResource($this->service->find($id)))->response();
    }

    #[OAT\Post(path: '/api/v1/adjustments', summary: 'Criar ajuste (corrige estoque)', tags: ['Ajustes'], security: [['sanctum' => []]], requestBody: new OAT\RequestBody(required: true, content: new OAT\JsonContent(properties: [new OAT\Property(property: 'product_id', type: 'integer'), new OAT\Property(property: 'warehouse_id', type: 'integer'), new OAT\Property(property: 'reason', type: 'string', enum: ['erro', 'quebra', 'perda', 'roubo', 'vencimento', 'correcao']), new OAT\Property(property: 'quantity', type: 'number', description: 'Positivo (ganho) ou negativo (perda)'), new OAT\Property(property: 'observation', type: 'string', nullable: true)])), responses: [new OAT\Response(response: 201, description: 'Criado'), new OAT\Response(response: 422, description: 'Validação')])]
    public function store(AdjustmentStoreRequest $request): JsonResponse
    {
        $dto = AdjustmentDto::fromArray(array_merge($request->validated(), [
            'user_id' => $request->user()->id,
        ]));
        $adjustment = $this->service->create($dto);

        return (new AdjustmentResource($adjustment))
            ->response()
            ->setStatusCode(201);
    }
}
