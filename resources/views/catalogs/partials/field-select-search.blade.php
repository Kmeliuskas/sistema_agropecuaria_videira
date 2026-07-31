@php
    $selectedId = old($field['name'], $field['value'] ?? '');
    $hasError = $errors->has($field['name']);
    $rawOptions = $field['options'] ?? [];
    
    // Se options for uma lista (ex: subcategorias com category_id), converte para id => label e mapeia relacionamentos
    $optionsMap = [];
    if (is_array($rawOptions) && isset($rawOptions[0]) && is_array($rawOptions[0])) {
        foreach ($rawOptions as $opt) {
            $optionsMap[$opt['id']] = $opt['name'];
        }
    } else {
        $optionsMap = $rawOptions;
    }
    
    $selectedLabel = $selectedId ? addslashes($optionsMap[$selectedId] ?? '') : '';
@endphp
@php
    $isSubcategory = str_contains($field['name'], 'subcategory_id');
    $isProduct = str_contains($field['name'], 'product_id');
    $isCategory = str_contains($field['name'], 'category_id');
    $isWarehouse = str_contains($field['name'], 'warehouse_id');
@endphp
<div class="relative" x-data="{
    open: false,
    search: '{{ $selectedLabel }}',
    selected: '{{ $selectedId }}',
    rawOptions: {{ json_encode($rawOptions) }},
    activeCategoryId: '{{ old('category_id', $product->category_id ?? '') }}',
    activeWarehouseId: '{{ $activeWarehouseId ?? old('warehouse_id', $stockBalance->warehouse_id ?? $item->warehouse_id ?? '') }}',
    hasError: {{ $hasError ? 'true' : 'false' }},

    get options() {
        @if($isSubcategory)
            const result = {};
            if (!this.activeCategoryId) return result;
            
            if (Array.isArray(this.rawOptions)) {
                this.rawOptions.forEach(opt => {
                    if (String(opt.category_id) === String(this.activeCategoryId)) {
                        result[opt.id] = opt.name;
                    }
                });
            }
            return result;
        @elseif($isProduct)
            const result = {};
            if (!this.activeWarehouseId) return result;

            if (Array.isArray(this.rawOptions)) {
                this.rawOptions.forEach(opt => {
                    const wId = typeof opt === 'object' ? opt.warehouse_id : null;
                    if (String(wId) === String(this.activeWarehouseId)) {
                        result[opt.id] = opt.name || (opt.internal_code ? opt.name + ' (' + opt.internal_code + ')' : opt.id);
                    }
                });
            }
            return result;
        @endif

        if (Array.isArray(this.rawOptions) && this.rawOptions.length && typeof this.rawOptions[0] === 'object') {
            const result = {};
            this.rawOptions.forEach(opt => { 
                result[opt.id] = opt.name + (opt.internal_code ? ' (' + opt.internal_code + ')' : ''); 
            });
            return result;
        }

        return this.rawOptions;
    },

    filtered() {
        const currentOpts = this.options;
        if (!this.search) return currentOpts;
        const s = this.search.toLowerCase();
        const result = {};
        Object.entries(currentOpts).forEach(([k, v]) => {
            if (String(v).toLowerCase().includes(s)) result[k] = v;
        });
        return result;
    },

    pick(id, label) {
        this.selected = id;
        this.search = label;
        this.open = false;
        this.hasError = false;
        @if($isCategory)
            $dispatch('category-selected', { id: id });
        @endif
        @if($isWarehouse)
            $dispatch('warehouse-selected', { id: id });
        @endif
        @if($isProduct)
            const opt = Array.isArray(this.rawOptions) ? this.rawOptions.find(o => String(o.id) === String(id)) : null;
            $dispatch('product-selected', { id: id, stock: opt ? opt.stock : 0 });
        @endif
    },

    clear() {
        this.selected = '';
        this.search = '';
        @if($isCategory)
            $dispatch('category-selected', { id: '' });
        @endif
        @if($isWarehouse)
            $dispatch('warehouse-selected', { id: '' });
        @endif
        @if($isProduct)
            $dispatch('product-selected', { id: '', stock: 0 });
        @endif
    }
}" 
@if($isSubcategory)
    @category-selected.window="
        activeCategoryId = $event.detail.id;
        if (selected && !options[selected]) {
            clear();
        }
    "
@endif
@if($isProduct)
    @warehouse-selected.window="
        activeWarehouseId = $event.detail.id;
        if (selected && !options[selected]) {
            clear();
        }
    "
@endif
@click.outside="open = false">

    <input type="hidden" name="{{ $field['name'] }}" :value="selected" {{ isset($field['required']) && $field['required'] ? 'required' : '' }}>

    <div class="relative">
        <input type="text"
            placeholder="Clique para selecionar..."
            x-model="search"
            @focus="open = true"
            @click="open = true"
            :class="{'rounded-b-none': open, 'border-danger focus:border-danger focus:ring-danger/30': hasError}"
            class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 pr-10"
            autocomplete="off">

        <button type="button" @click="clear()" x-show="selected" x-cloak
                class="absolute right-8 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <svg class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground transition-transform"
             :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>

    <div x-show="open" x-cloak class="absolute z-[100] left-0 mt-1 w-full min-w-[200px] rounded-lg border border-border bg-surface shadow-xl max-h-60 overflow-y-auto">
        <template x-for="[id, label] in Object.entries(filtered())" :key="id">
            <button type="button" @click="pick(id, label)"
                    class="w-full px-3 py-2 text-left text-sm hover:bg-muted transition-colors">
                <span x-text="label"></span>
            </button>
        </template>
        <div x-show="Object.keys(filtered()).length === 0" class="px-3 py-2 text-sm text-muted-foreground">
            <span>
                @if($isSubcategory)
                    <template x-if="!activeCategoryId"><span>Selecione uma categoria primeiro</span></template>
                    <template x-if="activeCategoryId"><span>Nenhuma opção encontrada</span></template>
                @elseif($isProduct)
                    <template x-if="!activeWarehouseId"><span>Selecione um almoxarifado primeiro</span></template>
                    <template x-if="activeWarehouseId"><span>Nenhuma opção encontrada</span></template>
                @else
                    <span>Nenhuma opção encontrada</span>
                @endif
            </span>
        </div>
    </div>

    @error($field['name'])
        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>