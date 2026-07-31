<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\DTOs\Inventory\InventoryDto;
use App\Application\DTOs\Inventory\InventoryItemDto;
use App\Application\Services\InventoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryCountRequest;
use App\Http\Requests\Inventory\InventoryStoreRequest;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

/**
 * Controller de Inventário. Cria (gera itens por modalidade), inicia contagem,
 * aponta item a item e finaliza com ajuste automático de estoque.
 */
class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $service,
    ) {}

    #[OAT\Get(
        path: '/api/v1/inventories',
        summary: 'Listar inventários',
        tags: ['Inventário'],
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'per_page', in: 'query', schema: new OAT\Schema(type: 'integer')),
            new OAT\Parameter(name: 'status', in: 'query', schema: new OAT\Schema(type: 'string')),
        ],
        responses: [new OAT\Response(response: 200, description: 'Lista paginada'), new OAT\Response(response: 401, description: 'Não autenticado')]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $results = $this->service->list($request->query(), $perPage);

        return InventoryResource::collection($results)->response();
    }

    #[OAT\Get(path: '/api/v1/inventories/{inventory}', summary: 'Detalhar inventário (itens book×counted)', tags: ['Inventário'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Detalhe')])]
    public function show(int $id): JsonResponse
    {
        return (new InventoryResource($this->service->find($id)))->response();
    }

    #[OAT\Post(
        path: '/api/v1/inventories',
        summary: 'Criar inventário (gera itens por modalidade)',
        tags: ['Inventário'],
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(required: true, content: new OAT\JsonContent(properties: [
            new OAT\Property(property: 'type', type: 'string', enum: ['general', 'partial', 'rotating', 'by_category', 'by_location', 'by_lot']),
            new OAT\Property(property: 'warehouse_id', type: 'integer', nullable: true),
            new OAT\Property(property: 'category_id', type: 'integer', nullable: true),
            new OAT\Property(property: 'description', type: 'string', nullable: true),
        ])),
        responses: [new OAT\Response(response: 201, description: 'Criado'), new OAT\Response(response: 422, description: 'Validação')]
    )]
    public function store(InventoryStoreRequest $request): JsonResponse
    {
        $dto = InventoryDto::fromArray(array_merge($request->validated(), [
            'responsible_id' => $request->validated('responsible_id') ?? $request->user()->id,
        ]));
        $inventory = $this->service->create($dto);

        return (new InventoryResource($inventory))
            ->response()
            ->setStatusCode(201);
    }

    #[OAT\Post(path: '/api/v1/inventories/{inventory}/start', summary: 'Iniciar contagem', tags: ['Inventário'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Em contagem')])]
    public function start(Request $request, int $id): JsonResponse
    {
        $this->authorize('execute', Inventory::findOrFail($id));

        return (new InventoryResource($this->service->start($id)))->response();
    }

    #[OAT\Post(path: '/api/v1/inventories/{inventory}/count', summary: 'Apontar contagem de item', tags: ['Inventário'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Item contado')])]
    public function count(InventoryCountRequest $request, int $id): JsonResponse
    {
        $dto = InventoryItemDto::fromArray(array_merge($request->validated(), [
            'counter_id' => $request->user()->id,
        ]));

        return (new InventoryItemResource($this->service->countItem($id, $dto)))
            ->response();
    }

    #[OAT\Post(path: '/api/v1/inventories/{inventory}/finalize', summary: 'Finalizar + ajuste automático', tags: ['Inventário'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Finalizado')])]
    public function finalize(Request $request, int $id): JsonResponse
    {
        $this->authorize('execute', Inventory::findOrFail($id));

        return (new InventoryResource($this->service->finalize($id)))->response();
    }

    #[OAT\Post(path: '/api/v1/inventories/{inventory}/cancel', summary: 'Cancelar', tags: ['Inventário'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Cancelado')])]
    public function cancel(Request $request, int $id): JsonResponse
    {
        return (new InventoryResource($this->service->cancel($id)))->response();
    }
}
