<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\DTOs\Transfer\TransferDto;
use App\Application\Services\TransferService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transfer\TransferStoreRequest;
use App\Http\Resources\TransferResource;
use App\Models\Transfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

/**
 * Controller de Transferência entre almoxarifados.
 * Cria (pending), embarca (ship: debita origem) e recebe (receive: credita destino).
 */
class TransferController extends Controller
{
    public function __construct(
        private readonly TransferService $service,
    ) {}

    #[OAT\Get(path: '/api/v1/transfers', summary: 'Listar transferências', tags: ['Transferências'], security: [['sanctum' => []]], parameters: [new OAT\Parameter(name: 'per_page', in: 'query', schema: new OAT\Schema(type: 'integer')), new OAT\Parameter(name: 'status', in: 'query', schema: new OAT\Schema(type: 'string'))], responses: [new OAT\Response(response: 200, description: 'Lista paginada')])]
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $results = $this->service->list($request->query(), $perPage);

        return TransferResource::collection($results)->response();
    }

    #[OAT\Get(path: '/api/v1/transfers/{transfer}', summary: 'Detalhar transferência', tags: ['Transferências'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Detalhe'), new OAT\Response(response: 404, description: 'Não encontrado')])]
    public function show(int $id): JsonResponse
    {
        return (new TransferResource($this->service->find($id)))->response();
    }

    #[OAT\Post(path: '/api/v1/transfers', summary: 'Criar transferência', tags: ['Transferências'], security: [['sanctum' => []]], requestBody: new OAT\RequestBody(required: true, content: new OAT\JsonContent(properties: [new OAT\Property(property: 'origin_warehouse_id', type: 'integer'), new OAT\Property(property: 'destination_warehouse_id', type: 'integer'), new OAT\Property(property: 'observation', type: 'string', nullable: true), new OAT\Property(property: 'items', type: 'array', items: new OAT\Items(properties: [new OAT\Property(property: 'product_id', type: 'integer'), new OAT\Property(property: 'quantity', type: 'number')]))])), responses: [new OAT\Response(response: 201, description: 'Criado'), new OAT\Response(response: 422, description: 'Validação')])]
    public function store(TransferStoreRequest $request): JsonResponse
    {
        $dto = TransferDto::fromArray(array_merge($request->validated(), [
            'requester_id' => $request->validated('requester_id') ?? $request->user()->id,
        ]));
        $transfer = $this->service->create($dto);

        return (new TransferResource($transfer))
            ->response()
            ->setStatusCode(201);
    }

    #[OAT\Post(path: '/api/v1/transfers/{transfer}/ship', summary: 'Embarcar (debita origem)', tags: ['Transferências'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Em trânsito'), new OAT\Response(response: 422, description: 'Transição inválida')])]
    public function ship(Request $request, int $id): JsonResponse
    {
        $this->authorize('ship', Transfer::findOrFail($id));

        return (new TransferResource($this->service->ship($id)))->response();
    }

    #[OAT\Post(path: '/api/v1/transfers/{transfer}/receive', summary: 'Receber (credita destino)', tags: ['Transferências'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Recebida'), new OAT\Response(response: 422, description: 'Transição inválida')])]
    public function receive(Request $request, int $id): JsonResponse
    {
        $this->authorize('receive', Transfer::findOrFail($id));
        $received = $request->input('items', []);

        return (new TransferResource($this->service->receive($id, $received)))->response();
    }

    #[OAT\Post(path: '/api/v1/transfers/{transfer}/cancel', summary: 'Cancelar', tags: ['Transferências'], security: [['sanctum' => []]], responses: [new OAT\Response(response: 200, description: 'Cancelada')])]
    public function cancel(Request $request, int $id): JsonResponse
    {
        return (new TransferResource($this->service->cancel($id)))->response();
    }
}
