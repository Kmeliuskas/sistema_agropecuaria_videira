<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Exibe a listagem de categorias.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::query()->latest();

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

        $categories = $query->paginate(20)->withQueryString();

        return view('catalogs.index', [
            'catalog' => 'categories',
            'title' => 'Categorias',
            'columns' => [
                'code' => 'Código',
                'name' => 'Nome',
                'description' => 'Descrição',
                'color' => 'Cor',
                'is_active' => 'Ativo',
            ],
            'items' => $categories,
        ]);
    }

    /**
     * Exibe o formulário de criação.
     */
    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('catalogs.create', [
            'catalog' => 'categories',
            'title' => 'Nova Categoria',
            'fields' => $this->getFormFields(),
        ]);
    }

    /**
     * Armazena a nova categoria.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:categories,code'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['boolean'],
        ]);

        Category::create($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoria criada com sucesso!');
    }

    /**
     * Exibe a categoria.
     */
    public function show(Category $category): View
    {
        $this->authorize('view', $category);

        return view('catalogs.show', [
            'catalog' => 'categories',
            'title' => 'Categorias',
            'item' => $category,
            'fields' => $this->getShowFields($category),
        ]);
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('catalogs.edit', [
            'catalog' => 'categories',
            'title' => 'Editar Categoria',
            'item' => $category,
            'fields' => $this->getFormFields($category),
            'isEdit' => true,
        ]);
    }

    /**
     * Atualiza a categoria.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:categories,code,' . $category->id],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['boolean'],
        ]);

        $category->update($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    /**
     * Remove a categoria.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoria excluída com sucesso!');
    }

    /**
     * Retorna os atributos associados a uma categoria (JSON paraAJAX).
     */
    public function attributes(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        $attributes = $category->attributes()
            ->orderByPivot('sort_order')
            ->get()
            ->map(function ($attr) {
                $options = is_string($attr->options) 
                    ? (json_decode($attr->options, true) ?: array_map('trim', explode(',', $attr->options)))
                    : (array) $attr->options;

                return [
                    'id' => $attr->id,
                    'name' => $attr->name,
                    'slug' => $attr->slug,
                    'type' => $attr->type,
                    'options' => array_values(array_filter($options)),
                ];
            });

        return response()->json([
            'attributes' => $attributes,
        ]);
    }

    /**
     * Associa atributos a uma categoria.
     */
    public function assignAttributes(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'attribute_ids' => ['required', 'array'],
            'attribute_ids.*' => ['exists:attributes,id'],
        ]);

        $category->attributes()->syncWithPivotValues(
            $validated['attribute_ids'],
            ['sort_order' => 0]
        );

        return redirect()
            ->route('categories.show', $category)
            ->with('success', 'Atributos associados à categoria com sucesso!');
    }

    /**
     * Desassocia um atributo de uma categoria.
     */
    public function unassignAttribute(Category $category, Attribute $attribute): RedirectResponse
    {
        $this->authorize('update', $category);

        $category->attributes()->detach($attribute->id);

        return redirect()
            ->route('categories.show', $category)
            ->with('success', 'Atributo desassociado da categoria.');
    }

    /**
     * Retorna os campos do formulário.
     */
    private function getFormFields(?Category $category = null): array
    {
        return [
            [
                'name' => 'code',
                'label' => 'Código',
                'type' => 'text',
                'required' => true,
                'value' => $category ? $category->code : old('code'),
            ],
            [
                'name' => 'name',
                'label' => 'Nome',
                'type' => 'text',
                'required' => true,
                'value' => $category ? $category->name : old('name'),
            ],
            [
                'name' => 'description',
                'label' => 'Descrição',
                'type' => 'textarea',
                'required' => false,
                'value' => $category ? $category->description : old('description'),
            ],
            [
                'name' => 'color',
                'label' => 'Cor (Hex)',
                'type' => 'color',
                'required' => false,
                'value' => $category ? $category->color : old('color', '#3B82F6'),
            ],
            [
                'name' => 'is_active',
                'label' => 'Ativo',
                'type' => 'checkbox',
                'required' => false,
                'value' => $category ? (bool)$category->is_active : true,
            ],
        ];
    }

    /**
     * Retorna os campos para visualização.
     */
    private function getShowFields(Category $category): array
    {
        $attributes = $category->attributes()->orderByPivot('sort_order')->get();

        $attrsHtml = $attributes->isNotEmpty()
            ? $attributes->map(fn ($attr) => "<span class='inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium bg-muted text-foreground'>{$attr->name}</span>")->join(' ')
            : '<span class="text-muted-foreground">Nenhum atributo associado</span>';

        return [
            ['label' => 'Código', 'value' => $category->code ?? '—'],
            ['label' => 'Nome', 'value' => $category->name ?? '—'],
            ['label' => 'Descrição', 'value' => $category->description ?? '—'],
            ['label' => 'Cor', 'value' => $category->color
                ? "<span style='display:inline-block;width:20px;height:20px;border-radius:4px;background:{$category->color};border:1px solid #ccc;'></span> {$category->color}"
                : '—'],
            ['label' => 'Ativo', 'value' => $category->is_active
                ? '<span class="badge badge-success">Ativo</span>'
                : '<span class="badge badge-danger">Inativo</span>'],
            ['label' => 'Atributos associados', 'value' => $attrsHtml],
        ];
    }
}