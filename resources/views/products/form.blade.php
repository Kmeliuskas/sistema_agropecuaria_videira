@extends('layouts.app')

@php
    $isEdit = isset($product);
    $title = $isEdit ? "Editar Produto — {$product->name}" : 'Novo Produto — WMS';
    $pageTitle = $isEdit ? "Editar Produto" : "Novo Produto";

    // Atributos da categoria selecionada (para editar)
    $categoryAttributes = $categoryAttributes ?? collect();
    $allAttributes = $allAttributes ?? collect();
@endphp

@section('title', $title)
@section('page_title', $pageTitle)

@section('content')
<div class="mx-auto max-w-3xl">
    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('products.update', $product) : route('products.store') }}" class="space-y-8">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="overflow-y-auto" style="max-height: 60vh;">
            <div class="space-y-6">
                <div class="card p-6">
                    <h2 class="mb-4 font-semibold text-foreground">Dados gerais</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Nome *</label>
                            <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required
                                class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                            @error ('name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Código interno *</label>
                            <input type="text" name="internal_code" value="{{ old('internal_code', $product->internal_code ?? '') }}" required
                                class="w-full rounded-lg border border-border px-3 py-2 text-sm font-mono shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                            @error ('internal_code') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Código de barras</label>
                            <input type="text" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}"
                                class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Preço de venda</label>
                            <input type="text" inputmode="numeric" data-money name="sale_price"
                                value="{{ old('sale_price', isset($product) ? number_format((float) $product->sale_price, 2, ',', '.') : '') }}"
                                placeholder="0,00"
                                class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Custo</label>
                            <input type="text" inputmode="numeric" data-money name="last_cost"
                                value="{{ old('last_cost', isset($product) ? number_format((float) $product->last_cost, 2, ',', '.') : '') }}"
                                placeholder="0,00"
                                class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                            @error ('last_cost') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-foreground">Descrição</label>
                            <textarea name="description" rows="2" class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">{{ old('description', $product->description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card p-6">
                    <h2 class="mb-4 font-semibold text-foreground">Classificação e estoque</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Categoria</label>
                            @include('catalogs.partials.field-select-search', ['field' => [
                                'name' => 'category_id',
                                'options' => $categories,
                                'value' => old('category_id', $product->category_id ?? ''),
                                'dataAttributeTarget' => 'category-attributes',
                            ], 'errors' => $errors])
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Subcategoria</label>
                            @include('catalogs.partials.field-select-search', ['field' => [
                                'name' => 'subcategory_id',
                                'options' => $subcategories,
                                'value' => old('subcategory_id', $product->subcategory_id ?? '')
                            ], 'errors' => $errors])
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Marca</label>
                            @include('catalogs.partials.field-select-search', ['field' => [
                                'name' => 'brand_id',
                                'options' => $brands,
                                'value' => old('brand_id', $product->brand_id ?? '')
                            ], 'errors' => $errors])
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Fabricante</label>
                            @include('catalogs.partials.field-select-search', ['field' => [
                                'name' => 'manufacturer_id',
                                'options' => $manufacturers,
                                'value' => old('manufacturer_id', $product->manufacturer_id ?? '')
                            ], 'errors' => $errors])
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Unidade *</label>
                            @include('catalogs.partials.field-select-search', ['field' => [
                                'name' => 'unit_id',
                                'options' => $units,
                                'value' => old('unit_id', $product->unit_id ?? ''),
                                'required' => true
                            ], 'errors' => $errors])
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Almoxarifado *</label>
                            @include('catalogs.partials.field-select-search', ['field' => [
                                'name' => 'warehouse_id',
                                'options' => $warehouses,
                                'value' => old('warehouse_id', $product->warehouse_id ?? ''),
                                'required' => true
                            ], 'errors' => $errors])
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Estoque mínimo</label>
                            <input type="text" inputmode="numeric" data-integer name="min_stock" value="{{ old('min_stock', isset($product) ? (int) $product->min_stock : '') }}"
                                placeholder="0"
                                class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Estoque máximo</label>
                            <input type="text" inputmode="numeric" data-integer name="max_stock" value="{{ old('max_stock', isset($product) ? (int) $product->max_stock : '') }}"
                                placeholder="0"
                                class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Estoque atual</label>
                            <input type="text" inputmode="numeric" data-integer name="current_stock" value="{{ old('current_stock', isset($product) ? (int) $product->current_stock : '0') }}"
                                placeholder="0"
                                class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox" name="active" value="1"
                                {{ old('active', $product->active ?? true) ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-border text-foreground">
                            <label class="text-sm text-foreground">Produto ativo</label>
                        </div>
                    </div>
                </div>

                <div class="card p-6" id="category-attributes">
                    <h2 class="mb-4 font-semibold text-foreground">Atributos específicos</h2>
                    <p class="mb-4 text-xs text-muted-foreground">Os campos abaixo são gerados automaticamente com base na categoria selecionada.</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" id="dynamic-attributes">
                        @foreach ($categoryAttributes as $attr)
                            <div data-attribute-id="{{ $attr['id'] }}">
                                <label class="mb-1 block text-sm font-medium text-foreground">{{ $attr['name'] }}</label>
                                @if ($attr['type'] === 'select' && !empty($attr['options']))
                                    <select name="attribute_values[{{ $attr['id'] }}]"
                                        class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                                        <option value="">Selecione...</option>
                                        @foreach (json_decode($attr['options'], true) ?? [] as $option)
                                            <option value="{{ $option }}"
                                                {{ old("attribute_values.{$attr['id']}", $product->attributes->where('id', $attr['id'])->first()->pivot->value ?? '') == $option ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif ($attr['type'] === 'boolean')
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="attribute_values[{{ $attr['id'] }}]" value="0">
                                        <input type="checkbox" name="attribute_values[{{ $attr['id'] }}]" value="1"
                                            {{ old("attribute_values.{$attr['id']}", $product->attributes->where('id', $attr['id'])->first()->pivot->value ?? '') ? 'checked' : '' }}
                                            class="h-4 w-4 rounded border-border text-foreground">
                                    </div>
                                @else
                                    <input type="text" name="attribute_values[{{ $attr['id'] }}]"
                                        value="{{ old("attribute_values.{$attr['id']}", $product->attributes->where('id', $attr['id'])->first()->pivot->value ?? '') }}"
                                        placeholder="ex: {{ $attr['name'] }}"
                                        class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('products.index') }}"
                class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted">
                Cancelar
            </a>
            <button type="submit"
                class="btn-primary">
                {{ $isEdit ? 'Salvar alterações' : 'Criar produto' }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    // Máscara de dinheiro: digita "100000" -> "1.000,00"
    function maskMoney(value) {
        let digits = (value || '').replace(/\D/g, '');
        if (digits === '') return '';
        let num = parseInt(digits, 10) / 100;
        return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.querySelectorAll('input[data-money]').forEach(function (input) {
        input.addEventListener('input', function () {
            input.value = maskMoney(input.value);
        });

        // Antes de enviar, converte "1.000,00" -> "1000.00"
        const form = input.closest('form');
        if (form && !form.dataset.moneyBound) {
            form.dataset.moneyBound = '1';
            form.addEventListener('submit', function () {
                document.querySelectorAll('input[data-money]').forEach(function (el) {
                    el.value = (el.value || '').replace(/\./g, '').replace(',', '.');
                });
            });
        }
    });

    // Máscara de inteiro: só dígitos, sem negativo (bloqueia 42,12 -> vira 4212)
    document.querySelectorAll('input[data-integer]').forEach(function (input) {
        input.addEventListener('input', function () {
            input.value = (input.value || '').replace(/\D/g, '');
        });
    });

    // Atualiza campos de atributos dinâmicos quando a categoria muda
    const categorySelect = document.querySelector('select[name="category_id"]');
    if (categorySelect) {
        categorySelect.addEventListener('change', function () {
            const categoryId = this.value;
            const container = document.getElementById('dynamic-attributes');
            const wrapper = document.getElementById('category-attributes');

            if (!categoryId || !container) {
                if (wrapper) wrapper.style.display = 'none';
                return;
            }

            // Busca atributos da categoria via endpoint AJAX
            fetch(`/api/categorias/${categoryId}/atributos`)
                .then(response => response.json())
                .then(data => {
                    const attributes = data.attributes || [];
                    container.innerHTML = '';

                    if (attributes.length === 0) {
                        if (wrapper) wrapper.style.display = 'none';
                        return;
                    }

                    if (wrapper) wrapper.style.display = '';

                    attributes.forEach(attr => {
                        const div = document.createElement('div');
                        div.setAttribute('data-attribute-id', attr.id);

                        let inputHtml = '';
                        if (attr.type === 'select' && attr.options && attr.options.length > 0) {
                            inputHtml = `<select name="attribute_values[${attr.id}]" class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                                <option value="">Selecione...</option>
                                ${attr.options.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                            </select>`;
                        } else if (attr.type === 'boolean') {
                            inputHtml = `<div class="flex items-center gap-2">
                                <input type="hidden" name="attribute_values[${attr.id}]" value="0">
                                <input type="checkbox" name="attribute_values[${attr.id}]" value="1" class="h-4 w-4 rounded border-border text-foreground">
                            </div>`;
                        } else {
                            inputHtml = `<input type="text" name="attribute_values[${attr.id}]" placeholder="ex: ${attr.name}" class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">`;
                        }

                        div.innerHTML = `
                            <label class="mb-1 block text-sm font-medium text-foreground">${attr.name}</label>
                            ${inputHtml}
                        `;
                        container.appendChild(div);
                    });

                    // Reaplica máscaras nos novos inputs
                    container.querySelectorAll('input[data-money]').forEach(function (input) {
                        input.addEventListener('input', function () {
                            this.value = maskMoney(this.value);
                        });
                    });
                    container.querySelectorAll('input[data-integer]').forEach(function (input) {
                        input.addEventListener('input', function () {
                            this.value = (this.value || '').replace(/\D/g, '');
                        });
                    });
                })
                .catch(() => {
                    if (wrapper) wrapper.style.display = 'none';
                });
        });

        // Trigger inicial se já tem categoria selecionada
        if (categorySelect.value) {
            categorySelect.dispatchEvent(new Event('change'));
        } else if (wrapper) {
            wrapper.style.display = 'none';
        }
    }
})();
</script>
@endpush

@endsection

