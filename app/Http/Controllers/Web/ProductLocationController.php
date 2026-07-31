<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductLocation;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductLocationController extends Controller
{
    /**
     * Lista todas as localizações de produtos.
     */
    public function index(): View
    {
        $this->authorize('viewAny', ProductLocation::class);

        $query = ProductLocation::query()
            ->with(['product', 'warehouse']);

        if ($search = request('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('internal_code', 'like', "%{$search}%");
            });
        }

        if (request('warehouse_id')) {
            $query->where('warehouse_id', request('warehouse_id'));
        }

        if (request('primary') === '1') {
            $query->where('is_primary', true);
        }

        $locations = $query->orderBy('warehouse_id')->orderBy('product_id')->paginate(25)->withQueryString();

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('product-locations.index', [
            'locations' => $locations,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Formulário de nova localização.
     */
    public function create(): View
    {
        $this->authorize('create', ProductLocation::class);

        return view('product-locations.form', $this->formData());
    }

    /**
     * Armazena uma nova localização.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ProductLocation::class);

        $data = $this->validateLocation($request);

        $location = ProductLocation::create($data);

        // Se for primária, desmarca outras localizações primárias do mesmo produto
        if ($data['is_primary']) {
            ProductLocation::where('product_id', $data['product_id'])
                ->where('id', '!=', $location->id)
                ->update(['is_primary' => false]);
        }

        return redirect()
            ->route('product-locations.index')
            ->with('success', 'Localização criada com sucesso.');
    }

    /**
     * Formulário de edição.
     */
    public function edit(ProductLocation $productLocation): View
    {
        $this->authorize('update', $productLocation);

        return view('product-locations.form', $this->formData() + ['location' => $productLocation]);
    }

    /**
     * Atualiza uma localização.
     */
    public function update(Request $request, ProductLocation $productLocation): RedirectResponse
    {
        $this->authorize('update', $productLocation);

        $data = $this->validateLocation($request, $productLocation);

        $productLocation->update($data);

        // Se for primária, desmarca outras localizações primárias do mesmo produto
        if ($data['is_primary']) {
            ProductLocation::where('product_id', $data['product_id'])
                ->where('id', '!=', $productLocation->id)
                ->update(['is_primary' => false]);
        }

        return redirect()
            ->route('product-locations.index')
            ->with('success', 'Localização atualizada com sucesso.');
    }

    /**
     * Remove uma localização.
     */
    public function destroy(ProductLocation $productLocation): RedirectResponse
    {
        $this->authorize('delete', $productLocation);

        $productLocation->delete();

        return redirect()
            ->route('product-locations.index')
            ->with('success', 'Localização removida.');
    }

    /**
     * Dados para o formulário.
     */
    protected function formData(): array
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'internal_code', 'warehouse_id']);

        return [
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->internal_code,
                'warehouse_id' => $p->warehouse_id,
            ])->values()->toArray(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray(),
        ];
    }

    /**
     * Validação de localização.
     */
    protected function validateLocation(Request $request, ?ProductLocation $location = null): array
    {
        $rules = [
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'aisle' => ['nullable', 'string', 'max:255'],
            'corridor' => ['nullable', 'string', 'max:255'],
            'shelf' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'is_primary' => ['boolean'],
        ];

        // Unique: um produto pode ter apenas uma localização por almoxarifado
        if ($location) {
            $rules['product_id'] = ['required', 'exists:products,id'];
        }

        $validated = $request->validate($rules);

        $validated['quantity'] = $validated['quantity'] ?? 0;
        $validated['is_primary'] = $request->boolean('is_primary');

        return $validated;
    }
}
