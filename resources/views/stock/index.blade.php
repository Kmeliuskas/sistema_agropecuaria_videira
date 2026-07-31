@extends('layouts.app')

@section('title', 'Estoque — WMS')
@section('page_title', 'Estoque')

@section('content')
<div class="space-y-4">
    <turbo-echo-stream-source type="private" channel="stock-balances"></turbo-echo-stream-source>

    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h2 class="text-sm font-medium text-muted-foreground">Posições de estoque por almoxarifado</h2>
        @can('stock.adjust')
        <a href="{{ route('stock.create') }}" class="btn-primary">
            + Nova posição
        </a>
        @endcan
    </div>

    <form method="GET" class="flex flex-wrap items-end gap-3 card p-4">
        <div class="flex-1 min-w-[200px]">
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Buscar produto</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome ou código"
                class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Almoxarifado</label>
            @include('catalogs.partials.field-select-search', ['field' => [
                'name' => 'warehouse_id',
                'options' => $warehouses->pluck('name', 'id')->toArray(),
                'value' => request('warehouse_id', '')
            ], 'errors' => $errors])
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm text-muted-foreground">
            <input type="checkbox" name="negative" value="1" {{ request('negative') ? 'checked' : '' }}
                class="h-4 w-4 rounded border-border text-foreground">
            Saldo negativo
        </label>
        <button type="submit" class="btn-primary">Filtrar</button>
    </form>

    <div class="overflow-x-auto card">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Produto</th>
                    <th class="px-4 py-3">Almoxarifado</th>
                    <th class="px-4 py-3">Localização (Rua/Prateleira)</th>
                    <th class="px-4 py-3 text-right">Atual</th>
                    <th class="px-4 py-3 text-right">Reservado</th>
                    <th class="px-4 py-3 text-right">Disponível</th>
                    <th class="px-4 py-3 text-right">Bloqueado</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody id="stock_table_body" class="divide-y divide-border">
                @forelse ($balances as $sb)
                    @include('stock._row')
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-muted-foreground/70">Nenhum saldo encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginação --}}
    <div class="card p-4">
        <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <span>Mostrar</span>
                <select name="per_page" onchange="updatePerPage(this.value)"
                    class="rounded-lg border border-border px-2 py-1 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                    @foreach ([5, 10, 15, 20, 25, 50] as $option)
                        <option value="{{ $option }}" {{ $balances->perPage() == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
                <span>por página</span>
            </div>

            {{ $balances->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
function updatePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    window.location.href = url.toString();
}
</script>
@endpush
@endsection
