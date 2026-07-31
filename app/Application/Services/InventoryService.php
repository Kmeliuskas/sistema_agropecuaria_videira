<?php

namespace App\Application\Services;

use App\Application\DTOs\Inventory\InventoryDto;
use App\Application\DTOs\Inventory\InventoryItemDto;
use App\Domain\Enums\InventoryStatus;
use App\Domain\Enums\InventoryType;
use App\Domain\Enums\MovementType;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\StockBalance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Caso de uso de Inventário.
 * Gera a lista de itens a contar (book_quantity) conforme a modalidade e,
 * ao finalizar, aplica ajustes automáticos no estoque via StockService quando
 * há diferença entre book e counted (entrada ou saída de ajuste).
 */
class InventoryService
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    public function create(InventoryDto $dto): Inventory
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();
            if (empty($data['code'])) {
                $data['code'] = $this->generateCode();
            }
            $inventory = Inventory::create($data);
            $this->generateItems($inventory, $dto->items ?? []);
            $this->refreshCounters($inventory);

            return $inventory->load('items.product');
        });
    }

    /**
     * Gera código legível e único: INV-AAMMDD-XXXX (sequencial por dia).
     */
    protected function generateCode(): string
    {
        $prefix = 'INV-'.now()->format('Ymd');
        $last = Inventory::where('code', 'like', "{$prefix}-%")
            ->orderByDesc('id')
            ->value('code');

        $seq = 1;
        if ($last) {
            $seq = (int) substr($last, strlen($prefix) + 1) + 1;
        }

        return "{$prefix}-".str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function find(int $id): Inventory
    {
        return Inventory::with(['items.product.unit', 'items.warehouse', 'warehouse', 'category', 'responsible'])
            ->findOrFail($id);
    }

    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Inventory::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /** Inicia a contagem (draft -> in_progress). */
    public function start(int $id): Inventory
    {
        return DB::transaction(function () use ($id) {
            $inventory = $this->requireStatus($id, InventoryStatus::DRAFT);
            $inventory->status = InventoryStatus::IN_PROGRESS->value;
            $inventory->started_at = now();
            $inventory->saveQuietly();

            return $inventory->load('items.product');
        });
    }

    /**
     * Aponta a contagem de um item: registra counted, calcula a diferença
     * (counted - book) e marca como contado. Não ajusta o estoque ainda
     * (apenas no finalize, em lote e atômico).
     */
    public function countItem(int $inventoryId, InventoryItemDto $dto): InventoryItem
    {
        return DB::transaction(function () use ($inventoryId, $dto) {
            $inventory = $this->requireStatus($inventoryId, InventoryStatus::IN_PROGRESS);
            $item = $inventory->items()
                ->where('product_id', $dto->productId)
                ->where('warehouse_id', $dto->warehouseId)
                ->firstOrFail();

            $counted = $dto->countedQuantity ?? 0;
            $item->counted_quantity = $counted;
            $item->difference = round($counted - (float) $item->book_quantity, 4);
            $item->is_counted = true;
            $item->counter_id = $dto->counterId ?? auth()->id();
            $item->counted_at = now();
            $item->saveQuietly();

            $this->refreshCounters($inventory);

            return $item->load('product', 'warehouse');
        });
    }

    /**
     * Finaliza o inventário e aplica ajustes automáticos: para cada item
     * contado com diferença, gera MovementType::ADJUST no Kardex (soma ou
     * subtrai a diferença), zerando o desvio. Move para 'finalized'.
     */
    public function finalize(int $id): Inventory
    {
        return DB::transaction(function () use ($id) {
            $inventory = $this->requireStatus($id, InventoryStatus::IN_PROGRESS);

            foreach ($inventory->items as $item) {
                if (! $item->is_counted || ! $item->hasDifference()) {
                    continue;
                }
                $this->stock->apply([
                    'product_id' => $item->product_id,
                    'type' => MovementType::ADJUST->value,
                    'reason' => 'Ajuste de inventário',
                    'source_type' => 'inventory',
                    'warehouse_id' => $item->warehouse_id,
                    'quantity' => (float) $item->difference, // pode ser + ou -
                    'document_number' => $inventory->code,
                    'observation' => "Inventário {$inventory->code} | item #{$item->id}",
                ]);
            }

            $inventory->status = InventoryStatus::FINALIZED->value;
            $inventory->finalized_at = now();
            $inventory->saveQuietly();

            return $inventory->load('items.product');
        });
    }

    public function cancel(int $id): Inventory
    {
        return DB::transaction(function () use ($id) {
            $inventory = $this->requireStatus($id, InventoryStatus::DRAFT);
            $inventory->status = InventoryStatus::CANCELLED->value;
            $inventory->saveQuietly();

            return $inventory->fresh();
        });
    }

    /**
     * Gera os itens a contar conforme a modalidade.
     * Itens explícitos no DTO têm precedência (inventário parcial manual);
     * caso contrário, deriva do estoque (geral/categoria/local). A quantidade
     * book é o saldo atual do produto no almoxarifado.
     */
    protected function generateItems(Inventory $inventory, array $explicit): void
    {
        if (! empty($explicit)) {
            foreach ($explicit as $dto) {
                $book = $dto->bookQuantity > 0
                    ? $dto->bookQuantity
                    : $this->bookQuantity($dto->productId, $dto->warehouseId);
                $inventory->items()->create([
                    'product_id' => $dto->productId,
                    'warehouse_id' => $dto->warehouseId,
                    'book_quantity' => $book,
                    'counted_quantity' => null,
                    'difference' => 0,
                    'is_counted' => false,
                ]);
            }

            return;
        }

        $products = $this->targetProducts($inventory);
        foreach ($products as $product) {
            $warehouses = $this->targetWarehouses($inventory, $product);
            foreach ($warehouses as $warehouseId) {
                $inventory->items()->create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'book_quantity' => $this->bookQuantity($product->id, $warehouseId),
                    'counted_quantity' => null,
                    'difference' => 0,
                    'is_counted' => false,
                ]);
            }
        }
    }

    protected function targetProducts(Inventory $inventory): Collection
    {
        $query = Product::query()->where('active', true);
        if ($inventory->typeEnum() === InventoryType::BY_CATEGORY && $inventory->category_id) {
            $query->where('category_id', $inventory->category_id);
        }

        return $query->get();
    }

    /**
     * @return int[]
     */
    protected function targetWarehouses(Inventory $inventory, Product $product): array
    {
        if ($inventory->warehouse_id) {
            return [$inventory->warehouse_id];
        }

        return StockBalance::where('product_id', $product->id)
            ->where('current', '>', 0)
            ->pluck('warehouse_id')
            ->unique()
            ->all();
    }

    protected function bookQuantity(int $productId, int $warehouseId): float
    {
        $balance = StockBalance::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return (float) ($balance?->current ?? 0);
    }

    protected function refreshCounters(Inventory $inventory): void
    {
        $inventory->items_count = $inventory->items()->count();
        $inventory->counted_count = $inventory->items()->where('is_counted', true)->count();
        $inventory->saveQuietly();
    }

    protected function requireStatus(int $id, InventoryStatus $expected): Inventory
    {
        $inventory = Inventory::with('items')->findOrFail($id);
        if ($inventory->status !== $expected->value) {
            throw ValidationException::withMessages([
                'status' => ["Transição inválida: estado atual '{$inventory->status}', esperado '{$expected->value}'."],
            ]);
        }

        return $inventory;
    }
}
