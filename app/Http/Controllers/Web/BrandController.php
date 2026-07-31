<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Exibe a listagem de marcas.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Brand::class);

        $query = Brand::query()->latest();

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

        $brands = $query->paginate(20)->withQueryString();

        return view('catalogs.index', [
            'catalog' => 'brands',
            'title' => 'Marcas',
            'columns' => [
                'code' => 'Código',
                'name' => 'Nome',
                'description' => 'Descrição',
                'website' => 'Site',
                'is_active' => 'Ativo',
            ],
            'items' => $brands,
        ]);
    }

    /**
     * Exibe o formulário de criação.
     */
    public function create(): View
    {
        $this->authorize('create', Brand::class);

        return view('catalogs.create', [
            'catalog' => 'brands',
            'title' => 'Nova Marca',
            'fields' => $this->getFormFields(),
        ]);
    }

    /**
     * Armazena a nova marca.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Brand::class);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:brands,code'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        Brand::create($validated);

        return redirect()
            ->route('brands.index')
            ->with('success', 'Marca criada com sucesso!');
    }

    /**
     * Exibe a marca.
     */
    public function show(Brand $brand): View
    {
        $this->authorize('view', $brand);

        return view('catalogs.show', [
            'catalog' => 'brands',
            'title' => 'Marcas',
            'item' => $brand,
            'fields' => $this->getShowFields($brand),
        ]);
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(Brand $brand): View
    {
        $this->authorize('update', $brand);

        return view('catalogs.edit', [
            'catalog' => 'brands',
            'title' => 'Editar Marca',
            'item' => $brand,
            'fields' => $this->getFormFields($brand),
            'isEdit' => true,
        ]);
    }

    /**
     * Atualiza a marca.
     */
    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $this->authorize('update', $brand);

        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:brands,code,' . $brand->id],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $brand->update($validated);

        return redirect()
            ->route('brands.index')
            ->with('success', 'Marca atualizada com sucesso!');
    }

    /**
     * Remove a marca.
     */
    public function destroy(Brand $brand): RedirectResponse
    {
        $this->authorize('delete', $brand);

        $brand->delete();

        return redirect()
            ->route('brands.index')
            ->with('success', 'Marca excluída com sucesso!');
    }

    /**
     * Retorna os campos do formulário.
     */
    private function getFormFields(?Brand $brand = null): array
    {
        return [
            [
                'name' => 'code',
                'label' => 'Código',
                'type' => 'text',
                'required' => true,
                'value' => $brand ? $brand->code : old('code'),
            ],
            [
                'name' => 'name',
                'label' => 'Nome',
                'type' => 'text',
                'required' => true,
                'value' => $brand ? $brand->name : old('name'),
            ],
            [
                'name' => 'description',
                'label' => 'Descrição',
                'type' => 'textarea',
                'required' => false,
                'value' => $brand ? $brand->description : old('description'),
            ],
            [
                'name' => 'website',
                'label' => 'Site',
                'type' => 'url',
                'required' => false,
                'value' => $brand ? $brand->website : old('website'),
            ],
            [
                'name' => 'is_active',
                'label' => 'Ativo',
                'type' => 'checkbox',
                'required' => false,
                'value' => $brand ? (bool)$brand->is_active : true,
            ],
        ];
    }

    /**
     * Retorna os campos para visualização.
     */
    private function getShowFields(Brand $brand): array
    {
        return [
            ['label' => 'Código', 'value' => $brand->code ?? '—'],
            ['label' => 'Nome', 'value' => $brand->name ?? '—'],
            ['label' => 'Descrição', 'value' => $brand->description ?? '—'],
            ['label' => 'Site', 'value' => $brand->website ? "<a href='{$brand->website}' target='_blank'>{$brand->website}</a>" : '—'],
            ['label' => 'Ativo', 'value' => $brand->is_active
                ? '<span class="badge badge-success">Ativo</span>'
                : '<span class="badge badge-danger">Inativo</span>'],
        ];
    }
}