<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WarehouseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', WarehouseType::class);

        $query = WarehouseType::query()->latest();

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

        $types = $query->paginate(20)->withQueryString();

        return view('catalogs.warehouse-types.index', [
            'types' => $types,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', WarehouseType::class);

        return view('catalogs.warehouse-types.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', WarehouseType::class);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:warehouse_types,code'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $type = WarehouseType::create($validated);

        return redirect()
            ->route('warehouse-types.index')
            ->with('success', "Tipo de almoxarifado {$type->name} criado com sucesso.");
    }

    public function show(WarehouseType $warehouseType): View
    {
        $this->authorize('view', $warehouseType);

        return view('catalogs.warehouse-types.show', [
            'type' => $warehouseType,
        ]);
    }

    public function edit(WarehouseType $warehouseType): View
    {
        $this->authorize('update', $warehouseType);

        return view('catalogs.warehouse-types.form', [
            'type' => $warehouseType,
        ]);
    }

    public function update(Request $request, WarehouseType $warehouseType): RedirectResponse
    {
        $this->authorize('update', $warehouseType);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:warehouse_types,code,' . $warehouseType->id],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $warehouseType->update($validated);

        return redirect()
            ->route('warehouse-types.index')
            ->with('success', "Tipo de almoxarifado {$warehouseType->name} atualizado com sucesso.");
    }

    public function destroy(WarehouseType $warehouseType): RedirectResponse
    {
        $this->authorize('delete', $warehouseType);

        $name = $warehouseType->name;
        $warehouseType->delete();

        return redirect()
            ->route('warehouse-types.index')
            ->with('success', "Tipo de almoxarifado {$name} removido.");
    }
}
