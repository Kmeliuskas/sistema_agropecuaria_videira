<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Exibe a listagem de unidades.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Unit::class);

        $query = Unit::query()->latest();

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        $active = request('active');
        if ($active === '0') {
            $query->where('is_active', false);
        } elseif ($active !== 'all') {
            $query->where('is_active', true);
        }

        $units = $query->paginate(20)->withQueryString();

        return view('catalogs.index', [
            'catalog' => 'units',
            'title' => 'Unidades',
            'columns' => [
                'code' => 'Código',
                'name' => 'Nome',
                'symbol' => 'Símbolo',
                'is_active' => 'Ativo',
            ],
            'items' => $units,
        ]);
    }

    /**
     * Exibe o formulário de criação.
     */
    public function create(): View
    {
        $this->authorize('create', Unit::class);

        return view('catalogs.create', [
            'catalog' => 'units',
            'title' => 'Nova Unidade',
            'fields' => $this->getFormFields(),
        ]);
    }

    /**
     * Armazena a nova unidade.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Unit::class);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:units,code'],
            'name' => ['required', 'string', 'max:50'],
            'symbol' => ['required', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        Unit::create($validated);

        return redirect()
            ->route('units.index')
            ->with('success', 'Unidade criada com sucesso!');
    }

    /**
     * Exibe a unidade.
     */
    public function show(Unit $unit): View
    {
        $this->authorize('view', $unit);

        return view('catalogs.show', [
            'catalog' => 'units',
            'title' => 'Unidades',
            'item' => $unit,
            'fields' => $this->getShowFields($unit),
        ]);
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(Unit $unit): View
    {
        $this->authorize('update', $unit);

        return view('catalogs.edit', [
            'catalog' => 'units',
            'title' => 'Editar Unidade',
            'item' => $unit,
            'fields' => $this->getFormFields($unit),
            'isEdit' => true,
        ]);
    }

    /**
     * Atualiza a unidade.
     */
    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $this->authorize('update', $unit);

        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:10', 'unique:units,code,' . $unit->id],
            'name' => ['sometimes', 'required', 'string', 'max:50'],
            'symbol' => ['sometimes', 'required', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $unit->update($validated);

        return redirect()
            ->route('units.index')
            ->with('success', 'Unidade atualizada com sucesso!');
    }

    /**
     * Remove a unidade.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorize('delete', $unit);

        $unit->delete();

        return redirect()
            ->route('units.index')
            ->with('success', 'Unidade excluída com sucesso!');
    }

    /**
     * Retorna os campos do formulário.
     */
    private function getFormFields(?Unit $unit = null): array
    {
        return [
            [
                'name' => 'code',
                'label' => 'Código',
                'type' => 'text',
                'required' => true,
                'value' => $unit ? $unit->code : old('code'),
            ],
            [
                'name' => 'name',
                'label' => 'Nome',
                'type' => 'text',
                'required' => true,
                'value' => $unit ? $unit->name : old('name'),
            ],
            [
                'name' => 'symbol',
                'label' => 'Símbolo',
                'type' => 'text',
                'required' => true,
                'value' => $unit ? $unit->symbol : old('symbol'),
            ],
            [
                'name' => 'is_active',
                'label' => 'Ativo',
                'type' => 'checkbox',
                'required' => false,
                'value' => $unit ? (bool)$unit->is_active : true,
            ],
        ];
    }

    /**
     * Retorna os campos para visualização.
     */
    private function getShowFields(Unit $unit): array
    {
        return [
            ['label' => 'Código', 'value' => $unit->code ?? '—'],
            ['label' => 'Nome', 'value' => $unit->name ?? '—'],
            ['label' => 'Símbolo', 'value' => $unit->symbol ?? '—'],
            ['label' => 'Ativo', 'value' => $unit->is_active
                ? '<span class="badge badge-success">Ativo</span>'
                : '<span class="badge badge-danger">Inativo</span>'],
        ];
    }
}