<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Manufacturer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ManufacturerController extends Controller
{
    /**
     * Exibe a listagem de fabricantes.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Manufacturer::class);

        $query = Manufacturer::query()->latest();

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $active = request('active');
        if ($active === '0') {
            $query->where('is_active', false);
        } elseif ($active !== 'all') {
            $query->where('is_active', true);
        }

        $manufacturers = $query->paginate(20)->withQueryString();

        return view('catalogs.index', [
            'catalog' => 'manufacturers',
            'title' => 'Fabricantes',
            'columns' => [
                'code' => 'Código',
                'name' => 'Nome',
                'document' => 'Documento',
                'website' => 'Site',
                'is_active' => 'Ativo',
            ],
            'items' => $manufacturers,
        ]);
    }

    /**
     * Exibe o formulário de criação.
     */
    public function create(): View
    {
        $this->authorize('create', Manufacturer::class);

        return view('catalogs.create', [
            'catalog' => 'manufacturers',
            'title' => 'Novo Fabricante',
            'fields' => $this->getFormFields(),
        ]);
    }

    /**
     * Armazena o novo fabricante.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Manufacturer::class);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:manufacturers,code'],
            'name' => ['required', 'string', 'max:100'],
            'document' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        Manufacturer::create($validated);

        return redirect()
            ->route('manufacturers.index')
            ->with('success', 'Fabricante criado com sucesso!');
    }

    /**
     * Exibe o fabricante.
     */
    public function show(Manufacturer $manufacturer): View
    {
        $this->authorize('view', $manufacturer);

        return view('catalogs.show', [
            'catalog' => 'manufacturers',
            'title' => 'Fabricantes',
            'item' => $manufacturer,
            'fields' => $this->getShowFields($manufacturer),
        ]);
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(Manufacturer $manufacturer): View
    {
        $this->authorize('update', $manufacturer);

        return view('catalogs.edit', [
            'catalog' => 'manufacturers',
            'title' => 'Editar Fabricante',
            'item' => $manufacturer,
            'fields' => $this->getFormFields($manufacturer),
            'isEdit' => true,
        ]);
    }

    /**
     * Atualiza o fabricante.
     */
    public function update(Request $request, Manufacturer $manufacturer): RedirectResponse
    {
        $this->authorize('update', $manufacturer);

        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:manufacturers,code,' . $manufacturer->id],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'document' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $manufacturer->update($validated);

        return redirect()
            ->route('manufacturers.index')
            ->with('success', 'Fabricante atualizado com sucesso!');
    }

    /**
     * Remove o fabricante.
     */
    public function destroy(Manufacturer $manufacturer): RedirectResponse
    {
        $this->authorize('delete', $manufacturer);

        $manufacturer->delete();

        return redirect()
            ->route('manufacturers.index')
            ->with('success', 'Fabricante excluído com sucesso!');
    }

    /**
     * Retorna os campos do formulário.
     */
    private function getFormFields(?Manufacturer $manufacturer = null): array
    {
        return [
            [
                'name' => 'code',
                'label' => 'Código',
                'type' => 'text',
                'required' => true,
                'value' => $manufacturer ? $manufacturer->code : old('code'),
            ],
            [
                'name' => 'name',
                'label' => 'Nome',
                'type' => 'text',
                'required' => true,
                'value' => $manufacturer ? $manufacturer->name : old('name'),
            ],
            [
                'name' => 'document',
                'label' => 'Documento (CNPJ/CPF)',
                'type' => 'text',
                'required' => false,
                'value' => $manufacturer ? $manufacturer->document : old('document'),
            ],
            [
                'name' => 'website',
                'label' => 'Site',
                'type' => 'url',
                'required' => false,
                'value' => $manufacturer ? $manufacturer->website : old('website'),
            ],
            [
                'name' => 'is_active',
                'label' => 'Ativo',
                'type' => 'checkbox',
                'required' => false,
                'value' => $manufacturer ? (bool)$manufacturer->is_active : true,
            ],
        ];
    }

    /**
     * Retorna os campos para visualização.
     */
    private function getShowFields(Manufacturer $manufacturer): array
    {
        return [
            ['label' => 'Código', 'value' => $manufacturer->code ?? '—'],
            ['label' => 'Nome', 'value' => $manufacturer->name ?? '—'],
            ['label' => 'Documento', 'value' => $manufacturer->document ?? '—'],
            ['label' => 'Site', 'value' => $manufacturer->website ? "<a href='{$manufacturer->website}' target='_blank'>{$manufacturer->website}</a>" : '—'],
            ['label' => 'Ativo', 'value' => $manufacturer->is_active
                ? '<span class="badge badge-success">Ativo</span>'
                : '<span class="badge badge-danger">Inativo</span>'],
        ];
    }
}