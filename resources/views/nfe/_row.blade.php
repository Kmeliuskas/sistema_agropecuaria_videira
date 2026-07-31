<tr>
    <td class="px-4 py-3">
        @include('catalogs.partials.field-select-search', [
            'field' => [
                'name' => "items[{$index}][warehouse_id]",
                'options' => $warehouses,
                'value' => $item->warehouse_id ?? '',
                'required' => true
            ],
            'errors' => $errors,
            'activeWarehouseId' => $item->warehouse_id ?? ''
        ])
    </td>
    <td class="px-4 py-3">
        @include('catalogs.partials.field-select-search', [
            'field' => [
                'name' => "items[{$index}][product_id]",
                'options' => $products,
                'value' => $item->product_id ?? '',
                'required' => true
            ],
            'errors' => $errors,
            'activeWarehouseId' => $item->warehouse_id ?? ''
        ])
    </td>
    <td class="px-4 py-3">
        <input type="number" name="items[{{ $index }}][quantity]" step="0.0001" min="0"
            value="{{ $item->quantity ?? '' }}"
            class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 item-qty" required>
    </td>
    <td class="px-4 py-3">
        <input type="number" name="items[{{ $index }}][unit_value]" step="0.01" min="0"
            value="{{ $item->unit_value ?? '' }}"
            class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 item-unit-value" required>
    </td>
    <td class="px-4 py-3 text-right text-muted-foreground item-total">
        {{ isset($item) ? number_format($item->quantity * $item->unit_value, 2, ',', '.') : '0,00' }}
    </td>
    <td class="px-4 py-3">
        @if ($index > 0 || isset($nfe))
            <button type="button" onclick="removeRow(this)" class="text-red-600 hover:text-red-700">Remover</button>
        @endif
    </td>
</tr>
