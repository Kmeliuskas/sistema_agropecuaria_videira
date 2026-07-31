@extends('layouts.app')

@section('title', 'Setores — WMS')
@section('page_title', 'Setores')

@section('content')
<div class="space-y-4">
    <turbo-echo-stream-source type="private" channel="sectors"></turbo-echo-stream-source>

    <div class="flex items-center justify-between">
        <h2 class="text-sm font-medium text-muted-foreground">Cadastro de setores</h2>
        @can('sectors.create')
        <a href="{{ route('sectors.create') }}"
            class="btn-primary">
            + Novo setor
        </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <form method="GET" class="flex flex-wrap items-end gap-3 card p-4">
        <div class="flex-1 min-w-[200px]">
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome ou código"
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
        <button type="submit" class="btn-primary">Filtrar</button>
        @if (request()->anyFilled(['search', 'active']))
            <a href="{{ route('sectors.index') }}" class="rounded-lg border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted">Limpar</a>
        @endif
    </form>

    {{-- Tabela --}}
    <div class="overflow-x-auto card">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Nome</th>
                    <th class="px-4 py-3">Descrição</th>
                    <th class="px-4 py-3">Situação</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody id="sectors_table_body" class="divide-y divide-border">
                @forelse ($sectors as $sector)
                    @include('sectors._row')
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground/70">Nenhum setor cadastrado.</td>
                    </tr>
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
                        <option value="{{ $option }}" {{ $sectors->perPage() == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
                <span>por página</span>
            </div>

            {{ $sectors->links() }}
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
