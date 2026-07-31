@extends('layouts.app')

@section('title', isset($nfe) ? 'Editar NF-E' : 'Nova NF-E')
@section('page_title', isset($nfe) ? 'Editar NF-E' : 'Nova NF-E')

@section('content')
<div class="space-y-4">
    <form method="POST" action="{{ isset($nfe) ? route('nfe.update', $nfe) : route('nfe.store') }}" id="nfeForm">
        @csrf
        @if(isset($nfe))
            @method('PUT')
        @endif

        <div class="card p-4">
            <h3 class="mb-4 text-lg font-medium">Dados da Nota Fiscal</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium">Fornecedor *</label>
                    <select name="supplier_id" class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30" required>
                        <option value="">Selecione</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ (old('supplier_id', $nfe->supplier_id ?? '') == $supplier->id) ? 'selected' : '' }}>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Número *</label>
                    <input type="text" name="number" value="{{ old('number', $nfe->number ?? '') }}"
                        class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Série *</label>
                    <input type="text" name="series" value="{{ old('series', $nfe->series ?? '') }}"
                        class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Data de Emissão *</label>
                    <input type="date" name="emission_date" value="{{ old('emission_date', isset($nfe) && $nfe->emission_date ? $nfe->emission_date->format('Y-m-d') : '') }}"
                        class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Data de Recebimento</label>
                    <input type="date" name="receipt_date" value="{{ old('receipt_date', isset($nfe) && $nfe->receipt_date ? $nfe->receipt_date->format('Y-m-d') : '') }}"
                        class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                </div>
            </div>
            <div class="mt-4">
                <label class="mb-1 block text-sm font-medium">Observação</label>
                <textarea name="observation" rows="2"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">{{ old('observation', $nfe->observation ?? '') }}</textarea>
            </div>
        </div>

        <div class="card p-4">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-medium">Produtos</h3>
                <button type="button" onclick="addRow()" class="btn-secondary text-sm">Adicionar Produto</button>
            </div>

            <div class="overflow-x-auto pb-32 min-h-[300px]">
                <table class="min-w-full divide-y divide-border text-sm" id="itemsTable">
                    <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">Almoxarifado *</th>
                            <th class="px-4 py-3">Produto *</th>
                            <th class="px-4 py-3">Quantidade *</th>
                            <th class="px-4 py-3">Valor Unitário *</th>
                            <th class="px-4 py-3">Valor Total</th>
                            <th class="px-4 py-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border" id="itemsTableBody">
                        @if(isset($nfe) && $nfe->items)
                            @foreach ($nfe->items as $index => $item)
                                @include('nfe._row', ['index' => $index, 'item' => $item])
                            @endforeach
                        @else
                            @include('nfe._row', ['index' => 0, 'item' => null])
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        @include('nfe._template_row')

        <div class="flex justify-end gap-3">
            <a href="{{ route('nfe.index') }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">Salvar</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
let rowIndex = {{ isset($nfe) && count($nfe->items) > 0 ? count($nfe->items) : 1 }};

function addRow() {
    const tbody = document.getElementById('itemsTableBody');
    const template = document.getElementById('rowTemplate');
    if (!template) return;

    const html = template.innerHTML.replace(/__INDEX__/g, rowIndex);
    const tempDiv = document.createElement('tbody');
    tempDiv.innerHTML = html.trim();
    const newRow = tempDiv.firstElementChild;

    tbody.appendChild(newRow);

    if (window.Alpine) {
        window.Alpine.initTree(newRow);
    }

    rowIndex++;
    attachEvents();
}

function removeRow(button) {
    const row = button.closest('tr');
    row.remove();
    updateTotals();
}

function attachEvents() {
    const rows = document.querySelectorAll('#itemsTableBody tr');
    rows.forEach(row => {
        const qty = row.querySelector('.item-qty');
        const unitValue = row.querySelector('.item-unit-value');
        if (qty && unitValue) {
            qty.addEventListener('input', updateTotals);
            unitValue.addEventListener('input', updateTotals);
        }
    });
}

function updateTotals() {
    const rows = document.querySelectorAll('#itemsTableBody tr');
    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value || 0);
        const unitValue = parseFloat(row.querySelector('.item-unit-value')?.value || 0);
        const total = qty * unitValue;
        const totalEl = row.querySelector('.item-total');
        if (totalEl) {
            totalEl.textContent = total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    });
}

// Initialize
attachEvents();
updateTotals();
</script>
@endpush
@endsection
