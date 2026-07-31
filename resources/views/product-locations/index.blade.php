@extends('layouts.app')

@section('title', 'Localização de Produtos — WMS')
@section('page_title', 'Localização de Produtos')

@section('content')
<div class="space-y-4">
    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h2 class="text-sm font-medium text-muted-foreground">Cadastro de localização física de produtos</h2>
        @can('products.create')
        <a href="{{ route('product-locations.create') }}" class="btn-primary">
            + Nova localização
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
            <select name="warehouse_id"
                class="rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                <option value="">Todos</option>
                @foreach ($warehouses as $wh)
                    <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm text-muted-foreground">
            <input type="checkbox" name="primary" value="1" {{ request('primary') ? 'checked' : '' }}
                class="h-4 w-4 rounded border-border text-foreground">
            Apenas primárias
        </label>
        <button type="submit" class="btn-primary">Filtrar</button>
        @if (request()->anyFilled(['search', 'warehouse_id', 'primary']))
            <a href="{{ route('product-locations.index') }}" class="rounded-lg border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted">Limpar</a>
        @endif
    </form>

    <div class="overflow-x-auto card">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Produto</th>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Almoxarifado</th>
                    <th class="px-4 py-3">Localização (Rua/Corredor/Prateleira/Nível/Posição)</th>
                    <th class="px-4 py-3 text-right">Quantidade</th>
                    <th class="px-4 py-3">Primária</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($locations as $location)
                    @include('product-locations._row')
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-muted-foreground/70">
                            Nenhuma localização encontrada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $locations->links() }}
</div>
@endsection
