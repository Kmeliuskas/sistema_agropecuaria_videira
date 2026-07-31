<tr id="{{ dom_id($product) }}" class="hover:bg-muted"
    x-data="productRowActions({
        productId: '{{ $product->id }}',
        editRoute: '{{ route('products.edit', $product) }}',
        deleteRoute: '{{ route('products.destroy', $product) }}'
    })">
    <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $product->internal_code }}</td>
    <td class="px-4 py-3">
        <a href="{{ route('products.show', $product) }}" class="font-medium text-foreground hover:underline">{{ $product->name }}</a>
    </td>
    <td class="px-4 py-3 text-muted-foreground">{{ $product->category?->name ?? '—' }}</td>
    <td class="px-4 py-3 text-muted-foreground">{{ $product->brand?->name ?? '—' }}</td>
    <td class="px-4 py-3 text-muted-foreground">{{ $product->warehouse?->name ?? '—' }}</td>
    <td class="num px-4 py-3 text-right {{ $product->current_stock < $product->min_stock ? 'text-danger font-medium' : 'text-foreground' }}">
        {{ number_format($product->current_stock, 0, ',', '.') }}
    </td>
    <td class="num px-4 py-3 text-right text-muted-foreground">{{ number_format($product->min_stock, 0, ',', '.') }}</td>
    <td class="px-4 py-3 text-muted-foreground">{{ $product->unit?->symbol ?? '—' }}</td>
    <td class="px-4 py-3">
        @if ($product->active)
            <span class="badge badge-success">Ativo</span>
        @else
            <span class="badge badge-muted">Inativo</span>
        @endif
    </td>
    <td class="px-4 py-3 text-right" x-ref="actions"></td>
    <turbo-echo-stream-source type="private" channel="stock.{{ $product->id }}"></turbo-echo-stream-source>
</tr>