<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\DTOs\MaterialRequest\MaterialRequestDto;
use App\Application\Services\MaterialRequestService;
use App\Http\Controllers\Controller;
use App\Http\Requests\MaterialRequest\MaterialRequestStoreRequest;
use App\Http\Resources\MaterialRequestResource;
use App\Models\MaterialRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

/**
 * Controller de Solicitação de Materiais. Expõe o ciclo de 6 etapas.
 * Apenas orquestra: valida, delega ao Service e serializa com Resource.
 */
class MaterialRequestController extends Controller
{
    public function __construct(
        private readonly MaterialRequestService $service,
    ) {}

    #[OAT\Get(
        path: '/api/v1/material-requests',
        summary: 'Listar solicitações de materiais',
        tags: ['Solicitação de Materiais'],
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

        return MaterialRequestResource::collection($results)->response();
    }

    #[OAT\Get(
        path: '/api/v1/material-requests/{material_request}',
        summary: 'Detalhar solicitação (itens aninhados)',
        tags: ['Solicitação de Materiais'],
        security: [['sanctum' => []]],
        responses: [new OAT\Response(response: 200, description: 'Detalhe'), new OAT\Response(response: 401, description: 'Não autenticado')]
    )]
    public function show(int $id): JsonResponse
    {
        return (new MaterialRequestResource($this->service->find($id)))->response();
    }

    #[OAT\Post(
        path: '/api/v1/material-requests',
        summary: 'Criar solicitação de materiais',
        tags: ['Solicitação de Materiais'],
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(required: true, content: new OAT\JsonContent(
            required: ['items'],
            properties: [
                new OAT\Property(property: 'justification', type: 'string'),
                new OAT\Property(property: 'items', type: 'array', items: new OAT\Items(properties: [
                    new OAT\Property(property: 'product_id', type: 'integer'),
                    new OAT\Property(property: 'quantity_requested', type: 'number'),
                    new OAT\Property(property: 'warehouse_id', type: 'integer', nullable: true),
                ])),
            ]
        )),
        responses: [new OAT\Response(response: 201, description: 'Criado'), new OAT\Response(response: 422, description: 'Validação')]
    )]
    public function store(MaterialRequestStoreRequest $request): JsonResponse
    {
        $dto = MaterialRequestDto::fromArray(array_merge($request->validated(), [
            'requester_id' => $request->user()->id,
        ]));
        $request_model = $this->service->create($dto);

        return (new MaterialRequestResource($request_model))
            ->response()
            ->setStatusCode(201);
    }

    #[OAT\Post(
        path: '/api/v1/material-requests/{material_request}/approve',
        summary: 'Aprovar (valida disponibilidade)',
        tags: ['Solicitação de Materiais'],
        security: [['sanctum' => []]],
        responses: [new OAT\Response(response: 200, description: 'Aprovado'), new OAT\Response(response: 422, description: 'Estoque insuficiente')]
    )]
    public function approve(Request $request, int $id): JsonResponse
    {
        $this->authorize('approve', MaterialRequest::findOrFail($id));

        return (new MaterialRequestResource(
            $this->service->approve($id, $request->input('observation'))
        ))->response();
    }

    #[OAT\Post(path: '/api/v1/material-requests/{material_request}/separate', summary: 'Separar', tags: ['Solicitação de Materiais'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Separado')])]
    public function separate(Request $request, int $id): JsonResponse
    {
        $this->authorize('separate', MaterialRequest::findOrFail($id));

        return (new MaterialRequestResource($this->service->separate($id)))->response();
    }

    #[OAT\Post(path: '/api/v1/material-requests/{material_request}/check', summary: 'Conferir', tags: ['Solicitação de Materiais'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Conferido')])]
    public function check(Request $request, int $id): JsonResponse
    {
        return (new MaterialRequestResource($this->service->check($id)))->response();
    }

    #[OAT\Post(path: '/api/v1/material-requests/{material_request}/deliver', summary: 'Entregar (consome estoque)', tags: ['Solicitação de Materiais'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Entregue')])]
    public function deliver(Request $request, int $id): JsonResponse
    {
        $this->authorize('deliver', MaterialRequest::findOrFail($id));

        return (new MaterialRequestResource($this->service->deliver($id)))->response();
    }

    #[OAT\Post(path: '/api/v1/material-requests/{material_request}/finish', summary: 'Finalizar', tags: ['Solicitação de Materiais'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Finalizado')])]
    public function finish(Request $request, int $id): JsonResponse
    {
        return (new MaterialRequestResource($this->service->finish($id)))->response();
    }

    #[OAT\Post(path: '/api/v1/material-requests/{material_request}/cancel', summary: 'Cancelar', tags: ['Solicitação de Materiais'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Cancelado')])]
    public function cancel(Request $request, int $id): JsonResponse
    {
        return (new MaterialRequestResource(
            $this->service->cancel($id, $request->input('observation'))
        ))->response();
    }
}
