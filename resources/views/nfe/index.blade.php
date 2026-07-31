@extends('layouts.app')

@section('title', 'NF-E — WMS')
@section('page_title', 'NF-E')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('nfe.create') }}" class="btn-primary">Nova NF-E</a>
    </div>

    <form method="GET" class="flex flex-wrap items-end gap-3 card p-4">
        <div class="flex-1 min-w-[200px]">
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Número, série ou fornecedor"
                class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
            <select name="status" class="rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                <option value="">Todos</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendente</option>
                <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Recebida</option>
                <option value="canceled" {{ request('status') === 'canceled' ? 'selected' : '' }}>Cancelada</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Fornecedor</label>
            <select name="supplier_id" class="rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                <option value="">Todos</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">Filtrar</button>
    </form>

    <div class="overflow-x-auto card">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Número</th>
                    <th class="px-4 py-3">Série</th>
                    <th class="px-4 py-3">Fornecedor</th>
                    <th class="px-4 py-3">Emissão</th>
                    <th class="px-4 py-3">Recebimento</th>
                    <th class="px-4 py-3 text-right">Valor Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($nfes as $nfe)
                    <tr class="hover:bg-muted">
                        <td class="px-4 py-3 font-medium">{{ $nfe->number }}</td>
                        <td class="px-4 py-3">{{ $nfe->series }}</td>
                        <td class="px-4 py-3">{{ $nfe->supplier?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $nfe->emission_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $nfe->receipt_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($nfe->total_value, 2, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $nfe->statusBadge() }}">{{ $nfe->statusLabel() }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @can('view', $nfe)
                                    <a href="{{ route('nfe.show', $nfe) }}" class="text-primary hover:underline">Ver</a>
                                @endcan
                                @can('update', $nfe)
                                    @if (in_array($nfe->status, ['pending', 'canceled']))
                                        <a href="{{ route('nfe.edit', $nfe) }}" class="text-muted-foreground hover:text-foreground">Editar</a>
                                    @endif
                                    @if ($nfe->status === 'pending')
                                        <form method="POST" action="{{ route('nfe.receive', $nfe) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-700">Receber</button>
                                        </form>
                                        <form method="POST" action="{{ route('nfe.cancel', $nfe) }}" class="inline"
                                            onsubmit="return confirm('Tem certeza que deseja cancelar esta nota fiscal?')">
                                            @csrf
                                            <button type="submit" class="text-orange-600 hover:text-orange-700">Cancelar</button>
                                        </form>
                                    @endif
                                @endcan
                                @can('delete', $nfe)
                                    @if ($nfe->status === 'pending')
                                        <form method="POST" action="{{ route('nfe.destroy', $nfe) }}" class="inline"
                                            onsubmit="return confirm('Tem certeza que deseja excluir esta nota fiscal?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-700">Excluir</button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-muted-foreground/70">Nenhuma nota fiscal encontrada.</td></tr>
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
                        <option value="{{ $option }}" {{ $nfes->perPage() == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
                <span>por página</span>
            </div>
            {{ $nfes->links() }}
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
