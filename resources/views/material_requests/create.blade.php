@extends('layouts.app')

@section('title', 'Nova Solicitação — WMS')
@section('page_title', 'Nova Solicitação de Material')

@section('content')
@php
    $productsJson = $products->map(function ($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'internal_code' => $p->internal_code,
            'warehouse_id' => $p->warehouse_id,
            'current_stock' => (float) $p->current_stock,
        ];
    });
@endphp
<div class="mx-auto max-w-3xl" x-data="mrForm()" @warehouse-selected.window="warehouseId = $event.detail.id">
    <form method="POST" action="{{ route('material-requests.store') }}" @submit.prevent="submit" x-ref="form" class="space-y-8">
        @csrf

        @if ($errors->any())
            <div class="alert-danger">
                <p class="font-medium">Não foi possível criar a solicitação:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Cabeçalho --}}
        <div class="card p-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-foreground">Setor solicitante</label>
                    @include('catalogs.partials.field-select-search', ['field' => [
                        'name' => 'sector_id',
                        'options' => $sectors,
                        'value' => old('sector_id', '')
                    ], 'errors' => $errors])
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-foreground">Almoxarifado que deseja solicitar <span class="text-danger">*</span></label>
                    @include('catalogs.partials.field-select-search', ['field' => [
                        'name' => 'warehouse_id',
                        'options' => $warehouses,
                        'value' => old('warehouse_id', ''),
                        'required' => true
                    ], 'errors' => $errors])
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-foreground">Justificativa</label>
                    <textarea name="justification" rows="2"
                        class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30"
                        placeholder="Motivo da solicitação (opcional)"></textarea>
                </div>
            </div>
        </div>

        {{-- Itens --}}
        <div class="card p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-semibold text-foreground">Itens solicitados</h2>
                <button type="button" @click="add()" :disabled="!warehouseId"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-medium text-foreground transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-50">
                    + Adicionar item
                </button>
            </div>

            @if (!$errors->has('warehouse_id'))
                <p class="mb-3 text-xs text-muted-foreground" x-show="!warehouseId">
                    Selecione o almoxarifado acima para listar os produtos disponíveis.
                </p>
            @endif

            <template x-for="(item, index) in items" :key="index">
                <div class="mb-3 grid grid-cols-1 gap-3 border-b border-border pb-3 sm:grid-cols-12">
                    <div class="sm:col-span-5">
                        <select x-model="item.product_id" :name="`items[${index}][product_id]`" required
                            class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                            <option value="">Produto...</option>
                            <template x-for="p in filteredProducts" :key="p.id">
                                <option :value="p.id" x-text="p.name + ' (' + p.internal_code + ')'"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-muted-foreground/70" x-show="filteredProducts.length === 0">
                            Nenhum produto neste almoxarifado.
                        </p>
                    </div>
                    <div class="sm:col-span-2">
                        <input type="number" step="0.0001" min="0.0001" x-model="item.quantity"
                            :name="`items[${index}][quantity]`" placeholder="Qtd"
                            class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                        <p class="mt-1 text-xs text-muted-foreground/70" x-show="selectedProduct(index)"
                            x-text="'Estoque atual: ' + (selectedProduct(index)?.current_stock ?? 0)"></p>
                    </div>
                    <div class="sm:col-span-4">
                        <input type="text" x-model="item.observation"
                            :name="`items[${index}][observation]`" placeholder="Observação"
                            class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                    </div>
                    <div class="sm:col-span-1 flex items-center justify-end">
                        <button type="button" @click="remove(index)"
                            class="btn-danger-outline px-2 py-1.5">✕</button>
                    </div>
                </div>
            </template>

            @error ('items')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('material-requests.index') }}"
                class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted">
                Cancelar
            </a>
            <button type="submit"
                class="btn-primary">
                Enviar solicitação
            </button>
        </div>
    </form>
</div>

<script>
function mrForm() {
    return {
        products: @json($productsJson),
        warehouseId: '',
        items: [{ product_id: '', quantity: '', observation: '' }],
        get filteredProducts() {
            if (!this.warehouseId) return [];
            return this.products.filter(p => p.warehouse_id == this.warehouseId);
        },
        selectedProduct(index) {
            const id = this.items[index]?.product_id;
            if (!id) return null;
            return this.products.find(p => p.id == id) ?? null;
        },
        add() { this.items.push({ product_id: '', quantity: '', observation: '' }); },
        remove(i) {
            if (this.items.length > 1) this.items.splice(i, 1);
            else this.items[0] = { product_id: '', quantity: '', observation: '' };
        },
        submit() {
            // Validação client-side: bloqueia o envio se a quantidade
            // exceder o estoque atual do produto (evita ida ao servidor).
            for (const [i, item] of this.items.entries()) {
                const product = this.selectedProduct(i);
                const qty = parseFloat(item.quantity);
                if (product && !isNaN(qty) && qty > product.current_stock) {
                    alert(
                        'Não é possível solicitar ' + qty + ' de "' + product.name +
                        '".\nO estoque atual disponível é de ' + product.current_stock + '.'
                    );
                    return;
                }
            }
            this.$refs.form.submit();
        },
    };
}
</script>
@endsection
