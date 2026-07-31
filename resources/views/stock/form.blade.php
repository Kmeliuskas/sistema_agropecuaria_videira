@extends('layouts.app')

@php
    $isEdit = isset($stockBalance);
    $title = $isEdit ? "Editar Posição de Estoque — WMS" : "Nova Posição de Estoque — WMS";
    $pageTitle = $isEdit ? "Editar Posição de Estoque" : "Nova Posição de Estoque";
@endphp

@section('title', $title)
@section('page_title', $pageTitle)

@section('content')
<div class="mx-auto max-w-2xl">
    <form method="POST" action="{{ $isEdit ? route('stock.update', $stockBalance) : route('stock.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="card p-6 space-y-4">
            <h2 class="font-semibold text-foreground">Informações de Estoque</h2>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-foreground">Almoxarifado *</label>
                    @include('catalogs.partials.field-select-search', ['field' => [
                        'name' => 'warehouse_id',
                        'options' => $warehouses,
                        'value' => old('warehouse_id', $stockBalance->warehouse_id ?? ''),
                        'required' => true
                    ], 'errors' => $errors])
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-foreground">Produto *</label>
                    @include('catalogs.partials.field-select-search', ['field' => [
                        'name' => 'product_id',
                        'options' => $products,
                        'value' => old('product_id', $stockBalance->product_id ?? ''),
                        'required' => true
                    ], 'errors' => $errors])
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-foreground">Rua / Ala (Aisle)</label>
                    <input type="text" name="aisle" 
                        value="{{ old('aisle', isset($stockBalance->product) ? $stockBalance->product->aisle : '') }}"
                        placeholder="Ex: Rua A"
                        class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-foreground">Corredor (Corridor)</label>
                    <input type="text" name="corridor" 
                        value="{{ old('corridor', isset($stockBalance->product) ? $stockBalance->product->corridor : '') }}"
                        placeholder="Ex: Corredor 02"
                        class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-foreground">Prateleira (Shelf)</label>
                    <input type="text" name="shelf" 
                        value="{{ old('shelf', isset($stockBalance->product) ? $stockBalance->product->shelf : '') }}"
                        placeholder="Ex: Prateleira 05"
                        class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-foreground">Andar / Nível (Level)</label>
                    <input type="text" name="level" 
                        value="{{ old('level', isset($stockBalance->product) ? $stockBalance->product->level : '') }}"
                        placeholder="Ex: 3º Andar"
                        class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-foreground">Posição / Gaveta (Position)</label>
                    <input type="text" name="position" 
                        value="{{ old('position', isset($stockBalance->product) ? $stockBalance->product->position : '') }}"
                        placeholder="Ex: Posição B12"
                        class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                </div>

                <div class="sm:col-span-2" x-data="{ currentStock: '{{ isset($stockBalance->product) ? (int)$stockBalance->product->current_stock : 0 }}' }" 
                     @product-selected.window="currentStock = $event.detail.stock || 0">
                    <label class="mb-1 block text-sm font-medium text-foreground">Estoque Atual (Cadastrado no Produto)</label>
                    <input type="text" :value="currentStock" disabled readonly
                        class="w-full rounded-lg border border-border bg-muted/50 px-3 py-2 text-sm text-muted-foreground shadow-sm cursor-not-allowed">
                    <p class="mt-1 text-xs text-muted-foreground">A quantidade de estoque é definida no cadastro do produto ou via movimentações.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('stock.index') }}"
                class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted">
                Cancelar
            </a>
            <button type="submit" class="btn-primary">
                {{ $isEdit ? 'Salvar alterações' : 'Cadastrar estoque' }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    document.querySelectorAll('input[data-integer]').forEach(function (input) {
        input.addEventListener('input', function () {
            input.value = (input.value || '').replace(/\D/g, '');
        });
    });
})();
</script>
@endpush
@endsection
