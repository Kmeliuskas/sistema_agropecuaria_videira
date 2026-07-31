<tr id="{{ dom_id($mr) }}" class="transition hover:bg-muted">
    <td class="px-5 py-3 font-medium text-foreground">{{ $mr->code }}</td>
    <td class="px-5 py-3 text-muted-foreground">{{ $mr->requester->name ?? '—' }}</td>
    <td class="px-5 py-3 text-muted-foreground">{{ $mr->items->count() }}</td>
    <td class="px-5 py-3">
        <span class="{{ $mr->statusEnum()->badgeClass() }}">
            {{ $mr->statusEnum()->label() }}
        </span>
    </td>
    <td class="px-5 py-3 text-muted-foreground">{{ $mr->created_at->format('d/m/Y H:i') }}</td>
    <td class="px-5 py-3 text-right">
        <a href="{{ route('material-requests.show', $mr) }}"
           class="rounded-lg border border-border px-3 py-1 text-xs font-medium text-muted-foreground transition hover:bg-muted">
            Ver
        </a>
    </td>
    <turbo-echo-stream-source type="private" channel="material-request.{{ $mr->id }}"></turbo-echo-stream-source>
</tr>
