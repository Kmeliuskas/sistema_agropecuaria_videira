@extends('layouts.app')

@section('title', 'Solicitações de Material — WMS')
@section('page_title', 'Solicitações de Material')

@section('content')
<div class="space-y-6">
    <turbo-echo-stream-source type="private" channel="material-requests"></turbo-echo-stream-source>
    {{-- Filtros + Novo --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-muted-foreground">Buscar</label>
                <input type="text" name="search" value="{{ $filters['search'] }}"
                    placeholder="Código ou justificativa"
                    class="w-64 rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
                <select name="status"
                    class="rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                    <option value="">Todos</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}" {{ ($filters['status'] ?? '') === $s->value ? 'selected' : '' }}>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                class="btn-primary">
                Filtrar
            </button>
        </form>

        @can('requests.create')
        <a href="{{ route('material-requests.create') }}"
            class="btn-primary">
            + Nova solicitação
        </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabela --}}
    <div class="overflow-x-auto card">
        <table class="min-w-full text-sm">
            <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th class="px-5 py-3">Código</th>
                    <th class="px-5 py-3">Solicitante</th>
                    <th class="px-5 py-3">Itens</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Data</th>
                    <th class="px-5 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody id="material_requests_table_body" class="divide-y divide-border">
                @forelse ($requests as $mr)
                    @include('material_requests._row')
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-muted-foreground/70">
                            Nenhuma solicitação encontrada.
                        </td>
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
                        <option value="{{ $option }}" {{ $requests->perPage() == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
                <span>por página</span>
            </div>

            {{ $requests->links() }}
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
