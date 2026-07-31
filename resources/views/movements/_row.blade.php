<tr id="{{ dom_id($m) }}" class="hover:bg-muted">
    <td class="px-4 py-3 text-muted-foreground">{{ $m->occurred_at?->format('d/m/Y H:i') ?? '—' }}</td>
    <td class="px-4 py-3">
        <a href="{{ route('products.show', $m->product_id) }}" class="font-medium text-foreground hover:underline">{{ $m->product?->name ?? '—' }}</a>
    </td>
    <td class="px-4 py-3">
        @php $labels = ['entry' => ['Entrada', 'badge-success'], 'exit' => ['Saída', 'badge-danger'], 'transfer' => ['Transferência', 'badge-primary'], 'adjustment' => ['Ajuste', 'badge-warning']]; @endphp
        @if (isset($labels[$m->type]))
            <span class="{{ $labels[$m->type][1] }}">{{ $labels[$m->type][0] }}</span>
        @else
            <span class="text-muted-foreground">{{ $m->type }}</span>
        @endif
    </td>
    <td class="px-4 py-3 text-muted-foreground">{{ $m->warehouse?->name ?? '—' }}</td>
    <td class="px-4 py-3 text-muted-foreground">{{ $m->reason ?? '—' }}</td>
    <td class="px-4 py-3 text-right text-foreground">{{ number_format($m->quantity, 0, ',', '.') }}</td>
    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($m->balance_before, 0, ',', '.') }}</td>
    <td class="px-4 py-3 text-right text-foreground">{{ number_format($m->balance_after, 0, ',', '.') }}</td>
    <td class="px-4 py-3 text-muted-foreground">{{ $m->user?->name ?? '—' }}</td>
</tr>
