<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Warehouse::class);

        $query = Warehouse::query()->with('warehouseType')->latest();

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (request('active') === '0') {
            $query->where('is_active', false);
        } elseif (request('active') !== 'all') {
            $query->where('is_active', true);
        }

        $perPage = request('per_page', 5);
        $perPage = in_array($perPage, [5, 10, 15, 20, 25, 50]) ? $perPage : 5;

        $warehouses = $query->paginate($perPage)->withQueryString();

        return view('warehouses.index', [
            'warehouses' => $warehouses,
        ]);
    }

    public function show(Warehouse $warehouse): View
    {
        $this->authorize('view', $warehouse);

        $warehouse->load(['warehouseType', 'products' => fn ($q) => $q->limit(5)]);

        return view('warehouses.show', [
            'warehouse' => $warehouse,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Warehouse::class);

        return view('warehouses.form', [
            'warehouseTypes' => $this->getWarehouseTypeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Warehouse::class);

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:30', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'warehouse_type_id' => ['nullable', 'exists:warehouse_types,id'],
            'responsible' => ['nullable', 'string', 'max:120'],
            'document' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:2'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_default'] = $request->boolean('is_default');

        $warehouse = Warehouse::create($validated);

        return redirect()
            ->route('warehouses.index')
            ->with('success', "Almoxarifado {$warehouse->name} criado com sucesso.");
    }

    public function edit(Warehouse $warehouse): View
    {
        $this->authorize('update', $warehouse);

        return view('warehouses.form', [
            'warehouse' => $warehouse,
            'warehouseTypes' => $this->getWarehouseTypeOptions($warehouse->warehouse_type_id),
        ]);
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $wasActive = $warehouse->is_active;

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:30', 'unique:warehouses,code,' . $warehouse->id],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'warehouse_type_id' => ['nullable', 'exists:warehouse_types,id'],
            'responsible' => ['nullable', 'string', 'max:120'],
            'document' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:2'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_default'] = $request->boolean('is_default');

        $warehouse->update($validated);

        if ($wasActive && !$validated['is_active']) {
            Product::where('warehouse_id', $warehouse->id)
                ->where('active', true)
                ->update(['active' => false]);
        } elseif (!$wasActive && $validated['is_active']) {
            Product::where('warehouse_id', $warehouse->id)
                ->where('active', false)
                ->update(['active' => true]);
        }

        return redirect()
            ->route('warehouses.index')
            ->with('success', "Almoxarifado {$warehouse->name} atualizado com sucesso.");
    }

    protected function getWarehouseTypeOptions(?int $selectedId = null): array
    {
        return WarehouseType::query()
            ->where(function ($q) use ($selectedId) {
                $q->where('is_active', true);
                if ($selectedId) {
                    $q->orWhere('id', $selectedId);
                }
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('delete', $warehouse);

        $name = $warehouse->name;
        $warehouse->delete();

        return redirect()
            ->route('warehouses.index')
            ->with('success', "Almoxarifado {$name} removido.");
    }
}
