<?php

namespace App\Application\Services;

use App\Application\DTOs\Product\ProductDto;
use App\Infrastructure\Repositories\ProductRepository;
use App\Models\Product;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Caso de uso de Produto. Orquestra Repository + regras (código único,
 * cálculo de disponível). Não conhece HTTP nem Eloquent diretamente.
 */
class ProductService
{
    public function __construct(
        private readonly ProductRepository $repository,
    ) {}

    public function list(array $filters = [], int $perPage = 15): Paginator
    {
        return $this->repository->list($filters, $perPage);
    }

    public function find(int $id): Product
    {
        return $this->repository->findOrFail($id);
    }

    public function create(ProductDto $dto): Product
    {
        $this->assertUniqueCode($dto->internalCode);
        $product = $this->repository->create($dto->toArray());
        $product->recalcAvailable();

        return $product;
    }

    public function update(int $id, ProductDto $dto): Product
    {
        $product = $this->repository->findOrFail($id);
        $this->assertUniqueCode($dto->internalCode, $id);

        $this->repository->update($product, $dto->toArray());
        $product->recalcAvailable();

        return $product->fresh();
    }

    public function delete(int $id): void
    {
        $product = $this->repository->findOrFail($id);
        $this->repository->delete($product);
    }

    protected function assertUniqueCode(string $code, ?int $ignoreId = null): void
    {
        $query = Product::where('internal_code', $code);
        if ($ignoreId) {
            $query->where('id', '<>', $ignoreId);
        }
        if ($query->exists()) {
            throw new ValidationException(
                Validator::make([], []),
                collect(['internal_code' => ['O código interno já está em uso.']])
            );
        }
    }
}
