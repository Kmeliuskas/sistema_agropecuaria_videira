<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query()
            ->with(['category', 'brand', 'unit', 'warehouse'])
            ->latest();

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('internal_code', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if (request('active') === '0') {
            $query->where('active', false);
        } elseif (request('active') !== 'all') {
            $query->where('active', true);
        }

        if (request('low_stock')) {
            $query->whereColumn('current_stock', '<', 'min_stock');
        }

        $perPage = request('per_page', 5);
        $perPage = in_array($perPage, [5, 10, 15, 20, 25, 50]) ? $perPage : 5;

        $products = $query->paginate($perPage)->withQueryString();

        return view('products.index', [
            'products' => $products,
        ]);
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        $product->load(['category', 'subcategory', 'brand', 'manufacturer', 'unit', 'warehouse', 'attributes', 'stockBalances' => function ($q) {
            $q->orderByDesc('updated_at');
        }, 'stockBalances.warehouse']);

        return view('products.show', [
            'product' => $product,
        ]);
    }

    /**
     * Formulário de novo produto.
     */
    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('products.form', $this->formData());
    }

    /**
     * Armazena um novo produto.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request);
        $this->normalizeNumeric($data);

        $attributeValues = $data['attribute_values'] ?? [];
        unset($data['attribute_values']);

        $product = Product::create($data);

        $this->syncAttributeValues($product, $attributeValues);

        return redirect()
            ->route('products.show', $product)
            ->with('success', "Produto {$product->name} criado com sucesso.");
    }

    /**
     * Formulário de edição.
     */
    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load('attributes');

        return view('products.form', $this->formData($product) + ['product' => $product]);
    }

    /**
     * Atualiza um produto.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validateProduct($request, $product);
        $this->normalizeNumeric($data);

        $attributeValues = $data['attribute_values'] ?? [];
        unset($data['attribute_values']);

        $product->update($data);
        $this->syncAttributeValues($product, $attributeValues);
        $product->broadcastRowUpdate();

        return redirect()
            ->route('products.show', $product)
            ->with('success', "Produto {$product->name} atualizado.");
    }

    /**
     * Sincroniza os valores de atributos de um produto.
     */
    protected function syncAttributeValues(Product $product, array $attributeValues): void
    {
        foreach ($attributeValues as $attributeId => $value) {
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                $product->attributes()->detach($attributeId);
                continue;
            }

            $stringValue = is_array($value) ? json_encode(array_values($value)) : (string) $value;

            $product->attributes()->syncWithoutDetaching([
                $attributeId => ['value' => $stringValue],
            ]);
        }
    }

    /**
     * Garante que campos numéricos NOT NULL recebam 0 quando vazios e
     * sincroniza o custo médio com o último custo informado (quando ausente).
     */
    protected function normalizeNumeric(array &$data): void
    {
        foreach (['min_stock', 'max_stock', 'current_stock', 'sale_price', 'last_cost', 'average_cost'] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            $data[$field] = $data[$field] === null || $data[$field] === '' ? 0 : $data[$field];
        }

        // Custo médio acompanha o último custo quando não informado.
        if (empty($data['average_cost']) && ! empty($data['last_cost'])) {
            $data['average_cost'] = $data['last_cost'];
        }

        $data['reserved_stock'] = 0;
        $data['available_stock'] = max(0, (float) $data['current_stock'] - (float) $data['reserved_stock']);
    }

    /**
     * Remove (soft delete) um produto.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $name = $product->name;
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', "Produto {$name} removido.");
    }

    /**
     * Dados para os selects do formulário (apenas cadastros ativos).
     */
    protected function formData(?Product $product = null): array
    {
        $getActiveOptions = function ($modelClass, $selectedId = null) {
            return $modelClass::query()
                ->where(function ($q) use ($selectedId) {
                    $q->where('is_active', true);
                    if ($selectedId) {
                        $q->orWhere('id', $selectedId);
                    }
                })
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        };

        $subcategories = Subcategory::query()
            ->where(function ($q) use ($product) {
                $q->where('is_active', true);
                if ($product && $product->subcategory_id) {
                    $q->orWhere('id', $product->subcategory_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'category_id']);

        $categoryId = $product?->category_id;
        $categoryAttributes = collect();
        if ($categoryId) {
            $categoryAttributes = Category::find($categoryId)?->attributes()
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
                }) ?? collect();
        }

        return [
            'categories' => $getActiveOptions(Category::class, $product?->category_id),
            'subcategories' => $subcategories->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'category_id' => $s->category_id
            ])->values()->toArray(),
            'brands' => $getActiveOptions(Brand::class, $product?->brand_id),
            'manufacturers' => $getActiveOptions(Manufacturer::class, $product?->manufacturer_id),
            'units' => $getActiveOptions(Unit::class, $product?->unit_id),
            'warehouses' => $getActiveOptions(Warehouse::class, $product?->warehouse_id),
            'categoryAttributes' => $categoryAttributes,
            'allAttributes' => Attribute::query()->where('is_active', true)->orderBy('sort_order')->get()->map(function ($attr) {
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
            }),
        ];
    }

    /**
     * Regras de validação do produto.
     */
    protected function validateProduct(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'internal_code' => ['required', 'string', 'max:60', 'unique:products,internal_code' . ($product ? ",{$product->id}" : '')],
            'barcode' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'manufacturer_id' => ['nullable', 'exists:manufacturers,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'last_cost' => ['nullable', 'numeric', 'min:0'],
            'average_cost' => ['nullable', 'numeric', 'min:0'],
            'active' => ['boolean'],
            'expiry_date' => ['nullable', 'date'],
            'expiry_alert_days' => ['nullable', 'integer', 'min:0'],
            'attribute_values' => ['nullable', 'array'],
            'attribute_values.*' => ['nullable'],
        ]);
    }
}
