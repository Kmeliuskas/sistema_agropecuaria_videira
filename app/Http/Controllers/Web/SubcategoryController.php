<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    /**
     * Exibe a listagem de subcategorias.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Subcategory::class);

        $query = Subcategory::query()->latest()->with('category');

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

        $subcategories = $query->paginate(20)->withQueryString();

        return view('catalogs.index', [
            'catalog' => 'subcategories',
            'title' => 'Subcategorias',
            'columns' => [
                'code' => 'Código',
                'name' => 'Nome',
                'category_id' => 'Categoria',
                'is_active' => 'Ativo',
            ],
            'items' => $subcategories,
        ]);
    }

    /**
     * Exibe o formulário de criação.
     */
    public function create(): View
    {
        $this->authorize('create', Subcategory::class);

        return view('catalogs.create', [
            'catalog' => 'subcategories',
            'title' => 'Nova Subcategoria',
            'fields' => $this->getFormFields(),
        ]);
    }

    /**
     * Armazena a nova subcategoria.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Subcategory::class);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:subcategories,code'],
            'name' => ['required', 'string', 'max:100'],
            'category_id' => ['required', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ]);

        Subcategory::create($validated);

        return redirect()
            ->route('subcategories.index')
            ->with('success', 'Subcategoria criada com sucesso!');
    }

    /**
     * Exibe a subcategoria.
     */
    public function show(Subcategory $subcategory): View
    {
        $this->authorize('view', $subcategory);

        return view('catalogs.show', [
            'catalog' => 'subcategories',
            'title' => 'Subcategorias',
            'item' => $subcategory->load('category'),
            'fields' => $this->getShowFields($subcategory),
        ]);
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(Subcategory $subcategory): View
    {
        $this->authorize('update', $subcategory);

        return view('catalogs.edit', [
            'catalog' => 'subcategories',
            'title' => 'Editar Subcategoria',
            'item' => $subcategory,
            'fields' => $this->getFormFields($subcategory),
            'isEdit' => true,
        ]);
    }

    /**
     * Atualiza a subcategoria.
     */
    public function update(Request $request, Subcategory $subcategory): RedirectResponse
    {
        $this->authorize('update', $subcategory);

        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:subcategories,code,' . $subcategory->id],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ]);

        $subcategory->update($validated);

        return redirect()
            ->route('subcategories.index')
            ->with('success', 'Subcategoria atualizada com sucesso!');
    }

    /**
     * Remove a subcategoria.
     */
    public function destroy(Subcategory $subcategory): RedirectResponse
    {
        $this->authorize('delete', $subcategory);

        $subcategory->delete();

        return redirect()
            ->route('subcategories.index')
            ->with('success', 'Subcategoria excluída com sucesso!');
    }

    /**
     * Retorna os campos do formulário.
     */
    private function getFormFields(?Subcategory $subcategory = null): array
    {
        $categories = Category::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->pluck('name', 'id')
            ->toArray();

        return [
            [
                'name' => 'code',
                'label' => 'Código',
                'type' => 'text',
                'required' => true,
                'value' => $subcategory ? $subcategory->code : old('code'),
            ],
            [
                'name' => 'name',
                'label' => 'Nome',
                'type' => 'text',
                'required' => true,
                'value' => $subcategory ? $subcategory->name : old('name'),
            ],
            [
                'name' => 'category_id',
                'label' => 'Categoria',
                'type' => 'select',
                'required' => true,
                'options' => $categories,
                'value' => $subcategory ? $subcategory->category_id : old('category_id'),
            ],
            [
                'name' => 'is_active',
                'label' => 'Ativo',
                'type' => 'checkbox',
                'required' => false,
                'value' => $subcategory ? (bool)$subcategory->is_active : true,
            ],
        ];
    }

    /**
     * Retorna os campos para visualização.
     */
    private function getShowFields(Subcategory $subcategory): array
    {
        return [
            ['label' => 'Código', 'value' => $subcategory->code ?? '—'],
            ['label' => 'Nome', 'value' => $subcategory->name ?? '—'],
            ['label' => 'Categoria', 'value' => $subcategory->category?->name ?? '—'],
            ['label' => 'Ativo', 'value' => $subcategory->is_active
                ? '<span class="badge badge-success">Ativo</span>'
                : '<span class="badge badge-danger">Inativo</span>'],
        ];
    }
}