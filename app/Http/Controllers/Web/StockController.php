<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', StockBalance::class);

        $query = StockBalance::query()
            ->with(['product' => fn ($q) => $q->with('unit'), 'warehouse'])
            ->whereHas('product');

        if ($search = request('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('internal_code', 'like', "%{$search}%");
            });
        }

        if (request('warehouse_id')) {
            $query->where('warehouse_id', request('warehouse_id'));
        }

        if (request('negative')) {
            $query->where('available', '<', 0);
        }

        $perPage = request('per_page', 5);
        $balances = $query->orderBy('product_id')->paginate($perPage)->withQueryString();

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('stock.index', [
            'balances' => $balances,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Formulário de nova posição de estoque.
     */
    public function create(): View
    {
        $this->authorize('create', StockBalance::class);

        return view('stock.form', $this->formData());
    }

    /**
     * Armazena uma nova posição de estoque.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StockBalance::class);

        $data = $this->validateStock($request);

        $product = Product::findOrFail($data['product_id']);
        $currentStock = (float) $product->current_stock;

        // Busca posição existente (incluindo deletadas) para reativar
        $stockBalance = StockBalance::withTrashed()
            ->where('product_id', $data['product_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->first();

        if ($stockBalance) {
            // Reativa posição deletada
            $stockBalance->restore();
            $stockBalance->update([
                'current' => $currentStock,
                'available' => max(0, $currentStock - (float)$product->reserved_stock),
            ]);
        } else {
            // Cria nova posição
            $stockBalance = StockBalance::create([
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'current' => $currentStock,
                'available' => max(0, $currentStock - (float)$product->reserved_stock),
            ]);
        }

        // Atualiza a localização física do Produto
        $product->update([
            'warehouse_id' => $data['warehouse_id'],
            'aisle' => $data['aisle'] ?? null,
            'corridor' => $data['corridor'] ?? null,
            'shelf' => $data['shelf'] ?? null,
            'level' => $data['level'] ?? null,
            'position' => $data['position'] ?? null,
        ]);

        return redirect()
            ->route('stock.index')
            ->with('success', 'Localização de estoque salva com sucesso.');
    }

    /**
     * Formulário de edição de estoque.
     */
    public function edit(StockBalance $stockBalance): View
    {
        $this->authorize('update', $stockBalance);

        $stockBalance->load('product');

        return view('stock.form', $this->formData() + ['stockBalance' => $stockBalance]);
    }

    /**
     * Atualiza uma posição de estoque.
     */
    public function update(Request $request, StockBalance $stockBalance): RedirectResponse
    {
        $this->authorize('update', $stockBalance);

        $data = $this->validateStock($request, $stockBalance);

        $product = Product::findOrFail($data['product_id']);

        $stockBalance->update([
            'product_id' => $data['product_id'],
            'warehouse_id' => $data['warehouse_id'],
            'current' => $product->current_stock,
        ]);
        $stockBalance->recalcAvailable();

        // Atualiza a localização física do Produto
        $product->update([
            'warehouse_id' => $data['warehouse_id'],
            'aisle' => $data['aisle'] ?? null,
            'corridor' => $data['corridor'] ?? null,
            'shelf' => $data['shelf'] ?? null,
            'level' => $data['level'] ?? null,
            'position' => $data['position'] ?? null,
        ]);

        return redirect()
            ->route('stock.index')
            ->with('success', 'Localização de estoque atualizada com sucesso.');
    }

    /**
     * Remove uma posição de estoque.
     */
    public function destroy(StockBalance $stockBalance): RedirectResponse
    {
        $this->authorize('delete', $stockBalance);

        $stockBalance->delete();

        return redirect()
            ->route('stock.index')
            ->with('success', 'Posição de estoque removida.');
    }

    /**
     * Dados para formulário.
     */
    protected function formData(): array
    {
        // Produtos que já têm posição de estoque ativa (não deletada)
        $productsWithActiveStock = StockBalance::whereNull('deleted_at')
            ->pluck('product_id')
            ->toArray();

        // Produtos disponíveis para nova posição:
        // - Não têm posição ativa, OU
        // - Têm posição deletada (para reativar)
        $products = Product::orderBy('name')
            ->get(['id', 'name', 'warehouse_id', 'current_stock']);

        return [
            'products' => $products->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'warehouse_id' => $p->warehouse_id,
                'stock' => (int) $p->current_stock,
                'has_active_stock' => in_array($p->id, $productsWithActiveStock),
            ])->values()->toArray(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray(),
        ];
    }

    /**
     * Validação de posição de estoque e localização.
     */
    protected function validateStock(Request $request, ?StockBalance $stockBalance = null): array
    {
        return $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'aisle' => ['nullable', 'string', 'max:255'],
            'corridor' => ['nullable', 'string', 'max:255'],
            'shelf' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
