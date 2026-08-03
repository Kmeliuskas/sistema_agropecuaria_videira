<div>
    <label class="mb-1 block text-sm font-medium text-foreground">
        {{ $field['label'] }}
        @if(isset($field['required']) && $field['required']) <span class="text-danger ml-1">*</span> @endif
    </label>
    @php
        $selectedValues = array_map('strval', (array) ($field['value'] ?? []));
        $options = $field['options'] ?? [];
    @endphp

    {{-- Campo hidden garante que se nada for selecionado, envia um array vazio limpando as categorias no backend --}}
    <input type="hidden" name="{{ $field['name'] }}" value="">

    <div class="rounded-lg border border-border bg-surface p-3 space-y-2 max-h-60 overflow-y-auto">
        @forelse($options as $id => $label)
            @php $isSelected = in_array((string)$id, $selectedValues, true); @endphp
            <label class="flex items-center justify-between gap-2 p-2 rounded-md hover:bg-muted/50 transition cursor-pointer border border-transparent {{ $isSelected ? 'border-primary/30 bg-primary/5' : '' }}">
                <div class="flex items-center gap-2.5">
                    <input type="checkbox" 
                           name="{{ $field['name'] }}[]" 
                           value="{{ $id }}" 
                           {{ $isSelected ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-border text-primary focus:ring-primary/30">
                    <span class="text-sm font-medium text-foreground">{{ $label }}</span>
                </div>
                @if($isSelected)
                    <span class="text-[11px] font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded">Selecionada</span>
                @endif
            </label>
        @empty
            <p class="text-xs text-muted-foreground p-2">Nenhuma categoria cadastrada.</p>
        @endforelse
    </div>
    <p class="mt-1 text-xs text-muted-foreground">Marque ou desmarque as categorias desejadas para este atributo.</p>
    @error($field['name'])
        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
