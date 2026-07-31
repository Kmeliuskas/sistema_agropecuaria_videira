<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\WarehouseType;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /** Mapa: nome do catálogo => [model, colunas da tabela, título, colunas de busca, rules de validação]. */
    private const CATALOGS = [
        'categories' => [
            'model' => Category::class,
            'title' => 'Categorias',
            'columns' => ['code' => 'Código', 'name' => 'Nome', 'description' => 'Descrição', 'color' => 'Cor', 'is_active' => 'Ativo'],
            'search' => ['name', 'code'],
            'rules' => [
                'code' => ['required', 'string', 'max:20', 'unique:categories,code'],
                'name' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:255'],
                'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'is_active' => ['boolean'],
            ],
            'rules_update' => [
                'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:categories,code,{id}'],
                'name' => ['sometimes', 'required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:255'],
                'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'is_active' => ['boolean'],
            ],
        ],
        'brands' => [
            'model' => Brand::class,
            'title' => 'Marcas',
            'columns' => ['code' => 'Código', 'name' => 'Nome', 'description' => 'Descrição', 'website' => 'Site', 'is_active' => 'Ativo'],
            'search' => ['name', 'code'],
            'rules' => [
                'code' => ['required', 'string', 'max:20', 'unique:brands,code'],
                'name' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:255'],
                'website' => ['nullable', 'url', 'max:255'],
                'is_active' => ['boolean'],
            ],
            'rules_update' => [
                'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:brands,code,{id}'],
                'name' => ['sometimes', 'required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:255'],
                'website' => ['nullable', 'url', 'max:255'],
                'is_active' => ['boolean'],
            ],
        ],
        'manufacturers' => [
            'model' => Manufacturer::class,
            'title' => 'Fabricantes',
            'columns' => ['code' => 'Código', 'name' => 'Nome', 'document' => 'Documento', 'website' => 'Site', 'is_active' => 'Ativo'],
            'search' => ['name', 'code'],
            'rules' => [
                'code' => ['required', 'string', 'max:20', 'unique:manufacturers,code'],
                'name' => ['required', 'string', 'max:100'],
                'document' => ['nullable', 'string', 'max:30'],
                'website' => ['nullable', 'url', 'max:255'],
                'is_active' => ['boolean'],
            ],
            'rules_update' => [
                'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:manufacturers,code,{id}'],
                'name' => ['sometimes', 'required', 'string', 'max:100'],
                'document' => ['nullable', 'string', 'max:30'],
                'website' => ['nullable', 'url', 'max:255'],
                'is_active' => ['boolean'],
            ],
        ],
        'suppliers' => [
            'model' => Supplier::class,
            'title' => 'Fornecedores',
            'columns' => ['code' => 'Código', 'name' => 'Nome', 'email' => 'E-mail', 'phone' => 'Telefone', 'contact_name' => 'Contato', 'is_active' => 'Ativo'],
            'search' => ['name', 'code', 'email', 'contact_name'],
            'rules' => [
                'code' => ['required', 'string', 'max:20', 'unique:suppliers,code'],
                'name' => ['required', 'string', 'max:150'],
                'document' => ['nullable', 'string', 'max:30'],
                'contact_name' => ['nullable', 'string', 'max:100'],
                'email' => ['nullable', 'email', 'max:150'],
                'phone' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:100'],
                'state' => ['nullable', 'string', 'max:2'],
                'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
                'is_active' => ['boolean'],
            ],
            'rules_update' => [
                'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:suppliers,code,{id}'],
                'name' => ['sometimes', 'required', 'string', 'max:150'],
                'document' => ['nullable', 'string', 'max:30'],
                'contact_name' => ['nullable', 'string', 'max:100'],
                'email' => ['nullable', 'email', 'max:150'],
                'phone' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:100'],
                'state' => ['nullable', 'string', 'max:2'],
                'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
                'is_active' => ['boolean'],
            ],
        ],
        'units' => [
            'model' => Unit::class,
            'title' => 'Unidades',
            'columns' => ['code' => 'Código', 'name' => 'Nome', 'symbol' => 'Símbolo', 'is_active' => 'Ativo'],
            'search' => ['name', 'code', 'symbol'],
            'rules' => [
                'code' => ['required', 'string', 'max:10', 'unique:units,code'],
                'name' => ['required', 'string', 'max:50'],
                'symbol' => ['required', 'string', 'max:10'],
                'is_active' => ['boolean'],
            ],
            'rules_update' => [
                'code' => ['sometimes', 'required', 'string', 'max:10', 'unique:units,code,{id}'],
                'name' => ['sometimes', 'required', 'string', 'max:50'],
                'symbol' => ['sometimes', 'required', 'string', 'max:10'],
                'is_active' => ['boolean'],
            ],
        ],
        'subcategories' => [
            'model' => Subcategory::class,
            'title' => 'Subcategorias',
            'columns' => ['code' => 'Código', 'name' => 'Nome', 'category_id' => 'Categoria', 'is_active' => 'Ativo'],
            'search' => ['name', 'code'],
            'rules' => [
                'code' => ['required', 'string', 'max:20', 'unique:subcategories,code'],
                'name' => ['required', 'string', 'max:100'],
                'category_id' => ['required', 'exists:categories,id'],
                'is_active' => ['boolean'],
            ],
            'rules_update' => [
                'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:subcategories,code,{id}'],
                'name' => ['sometimes', 'required', 'string', 'max:100'],
                'category_id' => ['sometimes', 'required', 'exists:categories,id'],
                'is_active' => ['boolean'],
            ],
        ],
        'warehouse-types' => [
            'model' => WarehouseType::class,
            'title' => 'Tipos de Almoxarifado',
            'columns' => ['code' => 'Código', 'name' => 'Nome', 'description' => 'Descrição', 'is_active' => 'Ativo'],
            'search' => ['name', 'code'],
            'rules' => [
                'code' => ['required', 'string', 'max:20', 'unique:warehouse_types,code'],
                'name' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:255'],
                'is_active' => ['boolean'],
            ],
            'rules_update' => [
                'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:warehouse_types,code,{id}'],
                'name' => ['sometimes', 'required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:255'],
                'is_active' => ['boolean'],
            ],
        ],
    ];

    /** Permissão de visualização exigida por catálogo. */
    private const CATALOG_PERMISSIONS = [
        'categories' => 'categories.view',
        'brands' => 'brands.view',
        'manufacturers' => 'manufacturers.view',
        'suppliers' => 'suppliers.view',
        'units' => 'units.view',
        'subcategories' => 'subcategories.view',
        'warehouse-types' => 'warehouses.view',
    ];

    /** Permissão de criação por catálogo. */
    private const CATALOG_CREATE_PERMISSIONS = [
        'categories' => 'categories.create',
        'brands' => 'brands.create',
        'manufacturers' => 'manufacturers.create',
        'suppliers' => 'suppliers.create',
        'units' => 'units.create',
        'subcategories' => 'subcategories.create',
        'warehouse-types' => 'warehouses.create',
    ];

    /** Permissão de edição por catálogo. */
    private const CATALOG_UPDATE_PERMISSIONS = [
        'categories' => 'categories.update',
        'brands' => 'brands.update',
        'manufacturers' => 'manufacturers.update',
        'suppliers' => 'suppliers.update',
        'units' => 'units.update',
        'subcategories' => 'subcategories.update',
        'warehouse-types' => 'warehouses.update',
    ];

    /** Permissão de exclusão por catálogo. */
    private const CATALOG_DELETE_PERMISSIONS = [
        'categories' => 'categories.delete',
        'brands' => 'brands.delete',
        'manufacturers' => 'manufacturers.delete',
        'suppliers' => 'suppliers.delete',
        'units' => 'units.delete',
        'subcategories' => 'subcategories.delete',
        'warehouse-types' => 'warehouses.delete',
    ];

    /**
     * Exibe a listagem do catálogo.
     */
    public function index(string $catalog): View
    {
        abort_unless(isset(self::CATALOGS[$catalog]), 404);

        $this->authorize('viewAny', self::CATALOGS[$catalog]['model']);

        $config = self::CATALOGS[$catalog];
        $model = $config['model'];

        $query = $model::query()->latest();

        if ($search = request('search')) {
            $query->where(function (Builder $q) use ($config, $search) {
                foreach ($config['search'] as $col) {
                    $q->orWhere($col, 'like', "%{$search}%");
                }
            });
        }

        // Filtro de situação (ativos/inativos/todos)
        $active = request('active');
        if ($active === '0') {
            $query->where('is_active', false);
        } elseif ($active !== 'all') {
            // Por padrão mostra apenas ativos
            $query->where('is_active', true);
        }

        $items = $query->paginate(20)->withQueryString();

        return view('catalogs.index', [
            'catalog' => $catalog,
            'title' => $config['title'],
            'columns' => $config['columns'],
            'items' => $items,
        ]);
    }

    /**
     * Exibe o formulário de criação.
     */
    public function create(string $catalog): View
    {
        abort_unless(isset(self::CATALOGS[$catalog]), 404);

        $this->authorize('create', self::CATALOGS[$catalog]['model']);

        $config = self::CATALOGS[$catalog];

        $extraData = [];
        if ($catalog === 'subcategories') {
            $extraData['categories'] = Category::query()->active()->orderBy('name')->get(['id', 'name']);
        }

        return view('catalogs.create', [
            'catalog' => $catalog,
            'title' => "Nova {$config['title']}",
            'fields' => $this->getFormFields($catalog),
            'extraData' => $extraData,
        ]);
    }

    /**
     * Armazena o novo registro.
     */
    public function store(Request $request, string $catalog): RedirectResponse
    {
        abort_unless(isset(self::CATALOGS[$catalog]), 404);

        $this->authorize('create', self::CATALOGS[$catalog]['model']);

        $config = self::CATALOGS[$catalog];
        $validated = $request->validate($config['rules']);

        $model = $config['model']::create($validated);

        return redirect()
            ->route("{$catalog}.index")
            ->with('success', "{$config['title']} criada com sucesso!");
    }

    /**
     * Exibe o registro.
     */
    public function show(string $catalog, $id): View
    {
        abort_unless(isset(self::CATALOGS[$catalog]), 404);

        $config = self::CATALOGS[$catalog];
        $model = $config['model'];

        $item = $model::findOrFail($id);

        $this->authorize('view', $item);

        return view('catalogs.show', [
            'catalog' => $catalog,
            'title' => $config['title'],
            'item' => $item,
            'fields' => $this->getShowFields($catalog, $item),
        ]);
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(string $catalog, $id): View
    {
        abort_unless(isset(self::CATALOGS[$catalog]), 404);

        $config = self::CATALOGS[$catalog];
        $model = $config['model'];

        $item = $model::findOrFail($id);

        $this->authorize('update', $item);

        $extraData = [];
        if ($catalog === 'subcategories') {
            $extraData['categories'] = Category::query()->active()->orderBy('name')->get(['id', 'name']);
        }

        return view('catalogs.edit', [
            'catalog' => $catalog,
            'title' => "Editar {$config['title']}",
            'item' => $item,
            'fields' => $this->getFormFields($catalog, $item),
            'extraData' => $extraData,
        ]);
    }

    /**
     * Atualiza o registro.
     */
    public function update(Request $request, string $catalog, $id): RedirectResponse
    {
        abort_unless(isset(self::CATALOGS[$catalog]), 404);

        $config = self::CATALOGS[$catalog];
        $model = $config['model'];

        $item = $model::findOrFail($id);

        $this->authorize('update', $item);

        // Substituir {id} nas rules de update
        $rules = [];
        foreach ($config['rules_update'] as $field => $rule) {
            if (is_array($rule)) {
                $rules[$field] = $rule;
            } else {
                $rules[$field] = str_replace('{id}', $item->id, $rule);
            }
        }

        $validated = $request->validate($rules);

        $item->update($validated);

        return redirect()
            ->route("{$catalog}.index")
            ->with('success', "{$config['title']} atualizada com sucesso!");
    }

    /**
     * Remove o registro.
     */
    public function destroy(string $catalog, $id): RedirectResponse
    {
        abort_unless(isset(self::CATALOGS[$catalog]), 404);

        $config = self::CATALOGS[$catalog];
        $model = $config['model'];

        $item = $model::findOrFail($id);

        $this->authorize('delete', $item);

        $item->delete();

        return redirect()
            ->route("{$catalog}.index")
            ->with('success', "{$config['title']} excluída com sucesso!");
    }

    /**
     * Retorna os campos do formulário para create/edit.
     */
    private function getFormFields(string $catalog, $item = null): array
    {
        $config = self::CATALOGS[$catalog];
        $fields = [];

        foreach ($config['rules'] as $field => $rules) {
            $isRequired = false;
            if (is_array($rules)) {
                $isRequired = in_array('required', $rules);
            } elseif (is_string($rules)) {
                $isRequired = str_contains($rules, 'required');
            }

            $fieldConfig = [
                'name' => $field,
                'label' => $this->getFieldLabel($field),
                'type' => $this->getFieldType($field, $catalog),
                'required' => $isRequired,
                'value' => $item ? $item->$field : old($field),
            ];

            // Opções para selects
            if ($field === 'category_id' && $catalog === 'subcategories') {
                $fieldConfig['type'] = 'select';
                $fieldConfig['options'] = $this->getCategoriesForSelect();
            }

            if ($field === 'is_active') {
                $fieldConfig['type'] = 'checkbox';
                $fieldConfig['value'] = $item ? (bool)$item->is_active : true;
            }

            if ($field === 'color') {
                $fieldConfig['type'] = 'color';
            }

            if ($field === 'rating') {
                $fieldConfig['type'] = 'number';
                $fieldConfig['step'] = '0.1';
                $fieldConfig['min'] = 0;
                $fieldConfig['max'] = 5;
            }

            $fields[] = $fieldConfig;
        }

        return $fields;
    }

    /**
     * Retorna os campos para visualização (show).
     */
    private function getShowFields(string $catalog, $item): array
    {
        $config = self::CATALOGS[$catalog];
        $fields = [];

        foreach ($config['columns'] as $field => $label) {
            $value = $item->$field;

            // Formatação especial para alguns campos
            if ($field === 'is_active') {
                $value = $value ? '<span class="badge badge-success">Ativo</span>' : '<span class="badge badge-danger">Inativo</span>';
            } elseif ($field === 'category_id') {
                $value = $item->category ? $item->category->name : '-';
            } elseif ($field === 'color') {
                $value = $value ? "<span style='display:inline-block;width:20px;height:20px;border-radius:4px;background:{$value};border:1px solid #ccc;'></span> {$value}" : '-';
            } elseif ($field === 'rating') {
                $value = $value ? "{$value}/5" : '-';
            } elseif ($value === null || $value === '') {
                $value = '-';
            }

            $fields[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $fields;
    }

    /**
     * Retorna label amigável para o campo.
     */
    private function getFieldLabel(string $field): string
    {
        $labels = [
            'code' => 'Código',
            'name' => 'Nome',
            'description' => 'Descrição',
            'color' => 'Cor',
            'website' => 'Site',
            'document' => 'Documento',
            'contact_name' => 'Nome do Contato',
            'email' => 'E-mail',
            'phone' => 'Telefone',
            'address' => 'Endereço',
            'city' => 'Cidade',
            'state' => 'Estado',
            'rating' => 'Avaliação (0-5)',
            'symbol' => 'Símbolo',
            'category_id' => 'Categoria',
            'is_active' => 'Ativo',
        ];

        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Retorna tipo do campo do formulário.
     */
    private function getFieldType(string $field, string $catalog): string
    {
        $types = [
            'code' => 'text',
            'name' => 'text',
            'description' => 'textarea',
            'color' => 'color',
            'website' => 'url',
            'document' => 'text',
            'contact_name' => 'text',
            'email' => 'email',
            'phone' => 'tel',
            'address' => 'textarea',
            'city' => 'text',
            'state' => 'text',
            'rating' => 'number',
            'symbol' => 'text',
            'category_id' => 'select',
            'is_active' => 'checkbox',
        ];

        return $types[$field] ?? 'text';
    }

    /**
     * Retorna categorias para select.
     */
    private function getCategoriesForSelect(): array
    {
        return Category::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->pluck('name', 'id')
            ->toArray();
    }
}