<tr id="{{ dom_id($sb) }}" class="hover:bg-muted">
    <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $sb->product?->internal_code }}</td>
    <td class="px-4 py-3">
        <a href="{{ route('products.show', $sb->product_id) }}" class="font-medium text-foreground hover:underline">{{ $sb->product?->name ?? '—' }}</a>
    </td>
    <td class="px-4 py-3 text-muted-foreground">{{ $sb->warehouse?->name ?? '—' }}</td>
    <td class="px-4 py-3 text-xs text-muted-foreground">
        @php
            $loc = array_filter([
                $sb->product?->aisle,
                $sb->product?->corridor,
                $sb->product?->shelf,
                $sb->product?->level,
                $sb->product?->position
            ]);
        @endphp
        {{ count($loc) > 0 ? implode(' • ', $loc) : '—' }}
    </td>
    <td class="px-4 py-3 text-right text-foreground">{{ number_format($sb->current, 0, ',', '.') }}</td>
    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($sb->reserved, 0, ',', '.') }}</td>
    <td class="px-4 py-3 text-right {{ $sb->available < 0 ? 'text-danger font-medium' : 'text-foreground' }}">{{ number_format($sb->available, 0, ',', '.') }}</td>
    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($sb->blocked, 0, ',', '.') }}</td>
    <td class="px-4 py-3 text-right whitespace-nowrap">
        <div class="flex justify-end gap-2">
            @can('stock.adjust')
            <a href="{{ route('stock.edit', $sb) }}"
               class="rounded-lg border border-border px-3 py-1 text-xs font-medium text-muted-foreground transition hover:bg-muted">
                Editar
            </a>
            <form method="POST" action="{{ route('stock.destroy', $sb) }}"
                  onsubmit="return confirm('Tem certeza que deseja excluir esta posição de estoque?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="rounded-lg border border-danger/30 px-3 py-1 text-xs font-medium text-danger transition hover:bg-danger/10">
                    Remover
                </button>
            </form>
            @endcan
        </div>
    </td>
    <turbo-echo-stream-source type="private" channel="stock.{{ $sb->product_id }}"></turbo-echo-stream-source>
</tr>
