<tr id="{{ dom_id($sector) }}" class="hover:bg-muted"
    x-data="sectorRowActions({
        sectorId: '{{ $sector->id }}',
        editRoute: '{{ route('sectors.edit', $sector) }}',
        deleteRoute: '{{ route('sectors.destroy', $sector) }}'
    })">
    <td class="px-4 py-3 font-medium text-foreground">{{ $sector->code ?: '—' }}</td>
    <td class="px-4 py-3 text-foreground">{{ $sector->name }}</td>
    <td class="px-4 py-3 text-muted-foreground">{{ $sector->description ?: '—' }}</td>
    <td class="px-4 py-3">
        @if ($sector->is_active)
            <span class="badge badge-success">Ativo</span>
        @else
            <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">Inativo</span>
        @endif
    </td>
    <td class="px-4 py-3">
        <div class="flex justify-end gap-3" x-ref="actions"></div>
    </td>
    <turbo-echo-stream-source type="private" channel="sector.{{ $sector->id }}"></turbo-echo-stream-source>
</tr>