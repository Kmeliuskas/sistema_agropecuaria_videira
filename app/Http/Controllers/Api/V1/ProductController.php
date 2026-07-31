<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\DTOs\Product\ProductDto;
use App\Application\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

/**
 * Controller de Produtos (REST). Apenas orquestra: valida via FormRequest,
 * delega ao ProductService (caso de uso) e serializa via Resource.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $service,
    ) {}

    #[OAT\Get(
        path: '/api/v1/products',
        summary: 'Listar produtos (filtros, paginação, include)',
        tags: ['Produtos'],
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'per_page', in: 'query', schema: new OAT\Schema(type: 'integer')),
            new OAT\Parameter(name: 'filter[search]', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'filter[category_id]', in: 'query', schema: new OAT\Schema(type: 'integer')),
            new OAT\Parameter(name: 'sort', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'include', in: 'query', schema: new OAT\Schema(type: 'string')),
        ],
        responses: [
            new OAT\Response(response: 200, description: 'Lista paginada', content: new OAT\JsonContent(ref: '#/components/schemas/Product')),
            new OAT\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $results = $this->service->list($request->query(), $perPage);

        return ProductResource::collection($results)->response();
    }

    public function show(int $id): JsonResponse
    {
        return (new ProductResource($this->service->find($id)))->response();
    }

    #[OAT\Post(
        path: '/api/v1/products',
        summary: 'Criar produto',
        tags: ['Produtos'],
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(ref: '#/components/schemas/Product')
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Criado', content: new OAT\JsonContent(ref: '#/components/schemas/Product')),
            new OAT\Response(response: 422, description: 'Validação'),
        ]
    )]
    public function store(ProductStoreRequest $request): JsonResponse
    {
        $dto = ProductDto::fromArray($request->validated());
        $product = $this->service->create($dto);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ProductUpdateRequest $request, int $id): JsonResponse
    {
        $dto = ProductDto::fromArray(array_merge($request->validated(), ['id' => $id]));
        $product = $this->service->update($id, $dto);

        return (new ProductResource($product))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(['message' => 'Produto excluído com sucesso.'], 200);
    }
}
