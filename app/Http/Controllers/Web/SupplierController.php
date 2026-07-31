<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Exibe a listagem de fornecedores.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Supplier::class);

        $query = Supplier::query()->latest();

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        $active = request('active');
        if ($active === '0') {
            $query->where('is_active', false);
        } elseif ($active !== 'all') {
            $query->where('is_active', true);
        }

        $suppliers = $query->paginate(20)->withQueryString();

        return view('catalogs.index', [
            'catalog' => 'suppliers',
            'title' => 'Fornecedores',
            'columns' => [
                'code' => 'Código',
                'name' => 'Nome',
                'email' => 'E-mail',
                'phone' => 'Telefone',
                'contact_name' => 'Contato',
                'is_active' => 'Ativo',
            ],
            'items' => $suppliers,
        ]);
    }

    /**
     * Exibe o formulário de criação.
     */
    public function create(): View
    {
        $this->authorize('create', Supplier::class);

        return view('catalogs.create', [
            'catalog' => 'suppliers',
            'title' => 'Novo Fornecedor',
            'fields' => $this->getFormFields(),
        ]);
    }

    /**
     * Armazena o novo fornecedor.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Supplier::class);

        $validated = $request->validate([
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
        ]);

        Supplier::create($validated);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Fornecedor criado com sucesso!');
    }

    /**
     * Exibe o fornecedor.
     */
    public function show(Supplier $supplier): View
    {
        $this->authorize('view', $supplier);

        return view('catalogs.show', [
            'catalog' => 'suppliers',
            'title' => 'Fornecedores',
            'item' => $supplier,
            'fields' => $this->getShowFields($supplier),
        ]);
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(Supplier $supplier): View
    {
        $this->authorize('update', $supplier);

        return view('catalogs.edit', [
            'catalog' => 'suppliers',
            'title' => 'Editar Fornecedor',
            'item' => $supplier,
            'fields' => $this->getFormFields($supplier),
            'isEdit' => true,
        ]);
    }

    /**
     * Atualiza o fornecedor.
     */
    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:suppliers,code,' . $supplier->id],
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
        ]);

        $supplier->update($validated);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Fornecedor atualizado com sucesso!');
    }

    /**
     * Remove o fornecedor.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Fornecedor excluído com sucesso!');
    }

    /**
     * Retorna os campos do formulário.
     */
    private function getFormFields(?Supplier $supplier = null): array
    {
        return [
            [
                'name' => 'code',
                'label' => 'Código',
                'type' => 'text',
                'required' => true,
                'value' => $supplier ? $supplier->code : old('code'),
            ],
            [
                'name' => 'name',
                'label' => 'Nome',
                'type' => 'text',
                'required' => true,
                'value' => $supplier ? $supplier->name : old('name'),
            ],
            [
                'name' => 'document',
                'label' => 'Documento (CNPJ/CPF)',
                'type' => 'text',
                'required' => false,
                'value' => $supplier ? $supplier->document : old('document'),
            ],
            [
                'name' => 'contact_name',
                'label' => 'Nome do Contato',
                'type' => 'text',
                'required' => false,
                'value' => $supplier ? $supplier->contact_name : old('contact_name'),
            ],
            [
                'name' => 'email',
                'label' => 'E-mail',
                'type' => 'email',
                'required' => false,
                'value' => $supplier ? $supplier->email : old('email'),
            ],
            [
                'name' => 'phone',
                'label' => 'Telefone',
                'type' => 'tel',
                'required' => false,
                'value' => $supplier ? $supplier->phone : old('phone'),
            ],
            [
                'name' => 'address',
                'label' => 'Endereço',
                'type' => 'textarea',
                'required' => false,
                'value' => $supplier ? $supplier->address : old('address'),
            ],
            [
                'name' => 'city',
                'label' => 'Cidade',
                'type' => 'text',
                'required' => false,
                'value' => $supplier ? $supplier->city : old('city'),
            ],
            [
                'name' => 'state',
                'label' => 'Estado (UF)',
                'type' => 'text',
                'required' => false,
                'value' => $supplier ? $supplier->state : old('state'),
            ],
            [
                'name' => 'rating',
                'label' => 'Avaliação (0-5)',
                'type' => 'number',
                'step' => '0.1',
                'min' => 0,
                'max' => 5,
                'required' => false,
                'value' => $supplier ? $supplier->rating : old('rating'),
            ],
            [
                'name' => 'is_active',
                'label' => 'Ativo',
                'type' => 'checkbox',
                'required' => false,
                'value' => $supplier ? (bool)$supplier->is_active : true,
            ],
        ];
    }

    /**
     * Retorna os campos para visualização.
     */
    private function getShowFields(Supplier $supplier): array
    {
        return [
            ['label' => 'Código', 'value' => $supplier->code ?? '—'],
            ['label' => 'Nome', 'value' => $supplier->name ?? '—'],
            ['label' => 'Documento', 'value' => $supplier->document ?? '—'],
            ['label' => 'Contato', 'value' => $supplier->contact_name ?? '—'],
            ['label' => 'E-mail', 'value' => $supplier->email ? "<a href='mailto:{$supplier->email}'>{$supplier->email}</a>" : '—'],
            ['label' => 'Telefone', 'value' => $supplier->phone ? "<a href='tel:{$supplier->phone}'>{$supplier->phone}</a>" : '—'],
            ['label' => 'Endereço', 'value' => $supplier->address ?? '—'],
            ['label' => 'Cidade/UF', 'value' => ($supplier->city ?? '') . ($supplier->city && $supplier->state ? ' - ' : '') . ($supplier->state ?? '') ?: '—'],
            ['label' => 'Avaliação', 'value' => $supplier->rating ? "{$supplier->rating}/5" : '—'],
            ['label' => 'Ativo', 'value' => $supplier->is_active
                ? '<span class="badge badge-success">Ativo</span>'
                : '<span class="badge badge-danger">Inativo</span>'],
        ];
    }
}