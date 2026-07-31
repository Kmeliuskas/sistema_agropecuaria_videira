<div id="{{ dom_id($wh) }}" class="card flex flex-col p-5"
    x-data="warehouseCardActions({
        warehouseId: '{{ $wh->id }}',
        editRoute: '{{ route('warehouses.edit', $wh) }}',
        deleteRoute: '{{ route('warehouses.destroy', $wh) }}'
    })">
    <div class="flex items-start justify-between">
        <div>
            <p class="font-mono text-xs text-muted-foreground/70">{{ $wh->code }}</p>
            <h3 class="font-semibold text-foreground">
                <a href="{{ route('warehouses.show', $wh) }}" class="hover:underline">{{ $wh->name }}</a>
            </h3>
        </div>
        <div class="flex flex-col items-end gap-2">
            @if ($wh->is_active)
                <span class="badge-success">Ativo</span>
            @else
                <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">Inativo</span>
            @endif
            @if ($wh->is_default)
                <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">Padrão</span>
            @endif
        </div>
    </div>
    @if ($wh->description)<p class="mt-2 text-sm text-muted-foreground">{{ $wh->description }}</p>@endif
    <div class="mt-3 space-y-1 text-xs text-muted-foreground">
        <p>Tipo: {{ $wh->warehouseType?->name ?? '—' }}</p>
        <p>Responsável: {{ $wh->responsible ?? '—' }}</p>
        <p>Cidade: {{ $wh->city ?? '—' }}/{{ $wh->state ?? '—' }}</p>
    </div>

    <div class="mt-auto flex justify-end gap-3 pt-4" x-ref="actions"></div>
    <turbo-echo-stream-source type="private" channel="warehouse.{{ $wh->id }}"></turbo-echo-stream-source>
</div>