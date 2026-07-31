@extends('layouts.app')

@section('title', 'Produtos — WMS')
@section('page_title', 'Produtos')

@section('content')
<div class="space-y-4">
    <turbo-echo-stream-source type="private" channel="products"></turbo-echo-stream-source>

    <div class="flex items-center justify-between">
        <h2 class="text-sm font-medium text-muted-foreground">Cadastro de produtos</h2>
        @can('products.create')
        <a href="{{ route('products.create') }}"
            class="btn-primary">
            + Novo produto
        </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <form method="GET" class="flex flex-wrap items-end gap-3 card p-4">
        <div class="flex-1 min-w-[200px]">
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome, código ou barcode"
                class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Situação</label>
            <select name="active" class="rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                <option value="" {{ request('active') === '' ? 'selected' : '' }}>Ativos</option>
                <option value="all" {{ request('active') === 'all' ? 'selected' : '' }}>Todos</option>
                <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inativos</option>
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm text-muted-foreground">
            <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }}
                class="h-4 w-4 rounded border-border text-foreground">
            Abaixo do mínimo
        </label>
        <button type="submit" class="btn-primary">Filtrar</button>
        @if (request()->anyFilled(['search', 'active', 'low_stock']))
            <a href="{{ route('products.index') }}" class="rounded-lg border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted">Limpar</a>
        @endif
    </form>

    {{-- Tabela --}}
    <div class="card">
        <div class="overflow-x-auto" style="max-height: 480px;">
            <table class="min-w-full divide-y divide-border text-sm">
                <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground sticky top-0">
                    <tr>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Nome</th>
                        <th class="px-4 py-3">Categoria</th>
                        <th class="px-4 py-3">Marca</th>
                        <th class="px-4 py-3">Almoxarifado</th>
                        <th class="px-4 py-3 text-right">Estoque</th>
                        <th class="px-4 py-3 text-right">Mín.</th>
                        <th class="px-4 py-3">Un.</th>
                        <th class="px-4 py-3">Situação</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="products_table_body" class="divide-y divide-border">
                    @forelse ($products as $product)
                        @include('products._row')
                    @empty
                        <tr><td colspan="10" class="px-4 py-8 text-center text-muted-foreground/70">Nenhum produto encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Paginação --}}
    <div class="card p-4">
        <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <span>Mostrar</span>
                <select name="per_page" onchange="updatePerPage(this.value)"
                    class="rounded-lg border border-border px-2 py-1 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                    @foreach ([5, 10, 15, 20, 25, 50] as $option)
                        <option value="{{ $option }}" {{ $products->perPage() == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
                <span>por página</span>
            </div>

            {{ $products->links() }}
        </div>
    </div>

    <script>
        function updatePerPage(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', value);
            window.location.href = url.toString();
        }
    </script>
</div>
@endsection