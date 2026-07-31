@extends('layouts.app')

@section('title', 'Almoxarifados — WMS')
@section('page_title', 'Almoxarifados')

@section('content')
<div class="space-y-4">
    <turbo-echo-stream-source type="private" channel="warehouses"></turbo-echo-stream-source>

    <div class="flex items-center justify-between">
        <h2 class="text-sm font-medium text-muted-foreground">Cadastro de almoxarifados</h2>
        @can('warehouses.create')
        <a href="{{ route('warehouses.create') }}" class="btn-primary">
            + Novo almoxarifado
        </a>
        @endcan
    </div>

    <form method="GET" class="flex flex-wrap items-end gap-3 card p-4">
        <div class="flex-1 min-w-[200px]">
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome ou código"
                class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Situação</label>
            <select name="active"
                class="rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                <option value="" {{ request('active') === '' || request('active') === null ? 'selected' : '' }}>Ativos</option>
                <option value="all" {{ request('active') === 'all' ? 'selected' : '' }}>Todos</option>
                <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inativos</option>
            </select>
        </div>
        <button type="submit" class="btn-primary">Filtrar</button>
        @if (request()->anyFilled(['search', 'active']))
            <a href="{{ route('warehouses.index') }}" class="rounded-lg border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted">Limpar</a>
        @endif
    </form>

    @php
        $hasMore = $warehouses->count() > 3;
    @endphp

    <div id="warehouses_list"
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
        @if ($hasMore) style="max-height: 500px; overflow-y: auto;" @endif>
        @forelse ($warehouses as $wh)
            @include('warehouses._card')
        @empty
            <p class="col-span-full card p-8 text-center text-muted-foreground/70">Nenhum almoxarifado encontrado.</p>
        @endforelse
    </div>

    {{-- Paginação --}}
    <div class="card p-4">
        <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <span>Mostrar</span>
                <select name="per_page" onchange="updatePerPage(this.value)"
                    class="rounded-lg border border-border px-2 py-1 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                    @foreach ([5, 10, 15, 20, 25, 50] as $option)
                        <option value="{{ $option }}" {{ $warehouses->perPage() == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
                <span>por página</span>
            </div>

            {{ $warehouses->links() }}
        </div>
    </div>
</div>

<script>
    function updatePerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', value);
        window.location.href = url.toString();
    }
</script>
@endsection
