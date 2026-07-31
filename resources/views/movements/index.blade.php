@extends('layouts.app')

@section('title', 'Movimentações — WMS')
@section('page_title', 'Movimentações')

@section('content')
<div class="space-y-4">
    <turbo-echo-stream-source type="private" channel="movements"></turbo-echo-stream-source>
    <form method="GET" class="flex flex-wrap items-end gap-3 card p-4">
        <div class="flex-1 min-w-[200px]">
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Buscar produto</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome ou código"
                class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Tipo</label>
            <select name="type" class="rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                <option value="">Todos</option>
                <option value="entry" {{ request('type') === 'entry' ? 'selected' : '' }}>Entrada</option>
                <option value="exit" {{ request('type') === 'exit' ? 'selected' : '' }}>Saída</option>
                <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transferência</option>
                <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Ajuste</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Almoxarifado</label>
            <select name="warehouse_id" class="rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                <option value="">Todos</option>
                @foreach ($warehouses as $wh)
                    <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">Filtrar</button>
    </form>

    <div class="overflow-x-auto card">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Data</th>
                    <th class="px-4 py-3">Produto</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Almoxarifado</th>
                    <th class="px-4 py-3">Motivo</th>
                    <th class="px-4 py-3 text-right">Qtd.</th>
                    <th class="px-4 py-3 text-right">Saldo antes</th>
                    <th class="px-4 py-3 text-right">Saldo após</th>
                    <th class="px-4 py-3">Usuário</th>
                </tr>
            </thead>
            <tbody id="movements_table_body" class="divide-y divide-border">
                @forelse ($movements as $m)
                    @include('movements._row')
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-muted-foreground/70">Nenhuma movimentação encontrada.</td></tr>
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
                        <option value="{{ $option }}" {{ $movements->perPage() == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
                <span>por página</span>
            </div>

            {{ $movements->links() }}
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
