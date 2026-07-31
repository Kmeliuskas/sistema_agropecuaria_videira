<tr id="{{ dom_id($location) }}">
    <td class="px-4 py-3">
        <span class="font-medium text-foreground">{{ $location->product?->name ?? '—' }}</span>
    </td>
    <td class="px-4 py-3 font-mono text-xs text-muted-foreground">
        {{ $location->product?->internal_code ?? '—' }}
    </td>
    <td class="px-4 py-3">
        {{ $location->warehouse?->name ?? '—' }}
    </td>
    <td class="px-4 py-3 text-sm text-muted-foreground">
        {{ $location->full_location }}
    </td>
    <td class="px-4 py-3 text-right">
        {{ number_format($location->quantity, 4, ',', '.') }}
    </td>
    <td class="px-4 py-3">
        @if ($location->is_primary)
            <span class="badge-success">Sim</span>
        @else
            <span class="text-muted-foreground">Não</span>
        @endif
    </td>
    <td class="px-4 py-3 text-right">
        <div class="flex justify-end gap-2">
            @can('products.update')
            <a href="{{ route('product-locations.edit', $location) }}"
               class="rounded-lg border border-border px-3 py-1 text-xs font-medium text-muted-foreground transition hover:bg-muted">
                Editar
            </a>
            @endcan
            @can('products.delete')
            <form method="POST" action="{{ route('product-locations.destroy', $location) }}"
                  onsubmit="return confirm('Remover esta localização?');">
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
</tr>
