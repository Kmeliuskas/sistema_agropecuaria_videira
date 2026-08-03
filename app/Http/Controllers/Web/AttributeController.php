<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    /**
     * Exibe a listagem de atributos.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Attribute::class);

        $query = Attribute::query()->with('categories')->latest();

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $active = request('active');
        if ($active === '0') {
            $query->where('is_active', false);
        } elseif ($active !== 'all') {
            $query->where('is_active', true);
        }

        $attributes = $query->paginate(20)->withQueryString();

        return view('catalogs.index', [
            'catalog' => 'attributes',
            'title' => 'Atributos',
            'columns' => [
                'name' => 'Nome',
                'slug' => 'Slug',
                'type' => 'Tipo',
                'categories' => 'Categorias',
                'is_active' => 'Ativo',
            ],
            'items' => $attributes,
        ]);
    }

    /**
     * Exibe o formulário de criação.
     */
    public function create(): View
    {
        $this->authorize('create', Attribute::class);

        return view('catalogs.create', [
            'catalog' => 'attributes',
            'title' => 'Novo Atributo',
            'fields' => $this->getFormFields(),
        ]);
    }

    /**
     * Armazena o novo atributo.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Attribute::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:attributes,slug'],
            'type' => ['required', 'string', 'max:20', 'in:text,number,select,multiselect,boolean,date'],
            'options' => ['nullable', 'string'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        $attribute = Attribute::create($validated);
        $attribute->categories()->syncWithPivotValues($categoryIds, ['sort_order' => 0]);

        return redirect()
            ->route('attributes.index')
            ->with('success', 'Atributo criado com sucesso!');
    }

    /**
     * Exibe o atributo.
     */
    public function show(Attribute $attribute): View
    {
        $this->authorize('view', $attribute);
        $attribute->load('categories');

        return view('catalogs.show', [
            'catalog' => 'attributes',
            'title' => 'Atributos',
            'item' => $attribute,
            'fields' => $this->getShowFields($attribute),
        ]);
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(Attribute $attribute): View
    {
        $this->authorize('update', $attribute);
        $attribute->load('categories');

        return view('catalogs.edit', [
            'catalog' => 'attributes',
            'title' => 'Editar Atributo',
            'item' => $attribute,
            'fields' => $this->getFormFields($attribute),
            'isEdit' => true,
        ]);
    }

    /**
     * Atualiza o atributo.
     */
    public function update(Request $request, Attribute $attribute): RedirectResponse
    {
        $this->authorize('update', $attribute);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['sometimes', 'required', 'string', 'max:100', 'unique:attributes,slug,' . $attribute->id],
            'type' => ['sometimes', 'required', 'string', 'max:20', 'in:text,number,select,multiselect,boolean,date'],
            'options' => ['nullable', 'string'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $categoryIds = $request->input('category_ids', []);
        $attribute->categories()->syncWithPivotValues($categoryIds, ['sort_order' => 0]);
        unset($validated['category_ids']);

        $attribute->update($validated);

        return redirect()
            ->route('attributes.index')
            ->with('success', 'Atributo atualizado com sucesso!');
    }

    /**
     * Remove o atributo.
     */
    public function destroy(Attribute $attribute): RedirectResponse
    {
        $this->authorize('delete', $attribute);

        $attribute->delete();

        return redirect()
            ->route('attributes.index')
            ->with('success', 'Atributo excluído com sucesso!');
    }

    /**
     * Retorna os campos do formulário.
     */
    private function getFormFields(?Attribute $attribute = null): array
    {
        $categoriesOptions = \App\Models\Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $selectedCategoryIds = $attribute 
            ? $attribute->categories->pluck('id')->toArray()
            : [];

        return [
            [
                'name' => 'name',
                'label' => 'Nome',
                'type' => 'text',
                'required' => true,
                'value' => $attribute ? $attribute->name : old('name'),
            ],
            [
                'name' => 'slug',
                'label' => 'Slug',
                'type' => 'text',
                'required' => true,
                'value' => $attribute ? $attribute->slug : old('slug'),
            ],
            [
                'name' => 'type',
                'label' => 'Tipo',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'text' => 'Texto', 
                    'number' => 'Número', 
                    'select' => 'Seleção Única (Select)', 
                    'multiselect' => 'Seleção Múltipla (Multiselect)', 
                    'boolean' => 'Booleano', 
                    'date' => 'Data'
                ],
                'value' => $attribute ? $attribute->type : old('type', 'text'),
            ],
            [
                'name' => 'category_ids',
                'label' => 'Categorias Vinculadas',
                'type' => 'category_multiselect',
                'required' => false,
                'options' => $categoriesOptions,
                'value' => old('category_ids', $selectedCategoryIds),
            ],
            [
                'name' => 'options',
                'label' => 'Opções (separadas por vírgula, para tipo select)',
                'type' => 'textarea',
                'required' => false,
                'value' => $attribute ? implode(', ', (array) $attribute->options) : old('options'),
            ],
            [
                'name' => 'sort_order',
                'label' => 'Ordem',
                'type' => 'number',
                'required' => false,
                'value' => $attribute ? $attribute->sort_order : old('sort_order', 0),
            ],
            [
                'name' => 'is_active',
                'label' => 'Ativo',
                'type' => 'checkbox',
                'required' => false,
                'value' => $attribute ? (bool) $attribute->is_active : true,
            ],
        ];
    }

    /**
     * Retorna os campos para visualização.
     */
    private function getShowFields(Attribute $attribute): array
    {
        $categoriesHtml = $attribute->categories->isNotEmpty()
            ? $attribute->categories->map(fn ($cat) => "<span class='inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium bg-muted text-foreground'>{$cat->name}</span>")->join(' ')
            : '<span class="text-muted-foreground">Nenhuma categoria associada</span>';

        return [
            ['label' => 'Nome', 'value' => $attribute->name ?? '—'],
            ['label' => 'Slug', 'value' => $attribute->slug ?? '—'],
            ['label' => 'Tipo', 'value' => $attribute->type ? ucfirst($attribute->type) : '—'],
            ['label' => 'Categorias associadas', 'value' => $categoriesHtml],
            ['label' => 'Opções', 'value' => $attribute->options ? implode(', ', (array) $attribute->options) : '—'],
            ['label' => 'Ordem', 'value' => $attribute->sort_order ?? 0],
            ['label' => 'Ativo', 'value' => $attribute->is_active
                ? '<span class="badge badge-success">Ativo</span>'
                : '<span class="badge badge-muted">Inativo</span>'],
        ];
    }
}

