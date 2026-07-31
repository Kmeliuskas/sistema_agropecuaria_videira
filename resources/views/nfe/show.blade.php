@extends('layouts.app')

@section('title', 'NF-E ' . $nfe->number . ' — WMS')
@section('page_title', 'NF-E ' . $nfe->number)

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('nfe.index') }}" class="text-muted-foreground hover:text-foreground">← Voltar</a>
            <span class="text-muted-foreground">|</span>
            <span class="font-medium">NF-E {{ $nfe->number }}/{{ $nfe->series }}</span>
        </div>
        <div class="flex items-center gap-2">
            @can('update', $nfe)
                @if (in_array($nfe->status, ['pending', 'canceled']))
                    <a href="{{ route('nfe.edit', $nfe) }}" class="btn-secondary">Editar</a>
                @endif
                @if ($nfe->status === 'pending')
                    <form method="POST" action="{{ route('nfe.receive', $nfe) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn-primary"
                            onclick="return confirm('Tem certeza que deseja receber esta nota fiscal? O estoque será atualizado automaticamente.')">
                            Receber Nota
                        </button>
                    </form>
                    <form method="POST" action="{{ route('nfe.cancel', $nfe) }}" class="inline"
                        onsubmit="return confirm('Tem certeza que deseja cancelar esta nota fiscal?')">
                        @csrf
                        <button type="submit" class="btn-secondary">Cancelar</button>
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
    </div>

    <div class="card p-4">
        <h3 class="mb-4 text-lg font-medium">Dados da Nota Fiscal</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="text-xs font-medium text-muted-foreground">Número</label>
                <p class="mt-1 font-medium">{{ $nfe->number }}</p>
            </div>
            <div>
                <label class="text-xs font-medium text-muted-foreground">Série</label>
                <p class="mt-1 font-medium">{{ $nfe->series }}</p>
            </div>
            <div>
                <label class="text-xs font-medium text-muted-foreground">Fornecedor</label>
                <p class="mt-1 font-medium">{{ $nfe->supplier?->name ?? '—' }}</p>
            </div>
            <div>
                <label class="text-xs font-medium text-muted-foreground">Status</label>
                <p class="mt-1">
                    <span class="badge {{ $nfe->statusBadge() }}">{{ $nfe->statusLabel() }}</span>
                </p>
            </div>
            <div>
                <label class="text-xs font-medium text-muted-foreground">Data de Emissão</label>
                <p class="mt-1">{{ $nfe->emission_date?->format('d/m/Y H:i') ?? '—' }}</p>
            </div>
            <div>
                <label class="text-xs font-medium text-muted-foreground">Data de Recebimento</label>
                <p class="mt-1">{{ $nfe->receipt_date?->format('d/m/Y H:i') ?? '—' }}</p>
            </div>
            <div>
                <label class="text-xs font-medium text-muted-foreground">Valor Total</label>
                <p class="mt-1 font-medium">{{ number_format($nfe->total_value, 2, ',', '.') }}</p>
            </div>
            <div>
                <label class="text-xs font-medium text-muted-foreground">Usuário</label>
                <p class="mt-1">{{ $nfe->user?->name ?? '—' }}</p>
            </div>
        </div>
        @if ($nfe->observation)
            <div class="mt-4">
                <label class="text-xs font-medium text-muted-foreground">Observação</label>
                <p class="mt-1 text-sm">{{ $nfe->observation }}</p>
            </div>
        @endif
    </div>

    <div class="card p-4">
        <h3 class="mb-4 text-lg font-medium">Itens da Nota</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border text-sm">
                <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3">Produto</th>
                        <th class="px-4 py-3">Almoxarifado</th>
                        <th class="px-4 py-3 text-right">Quantidade</th>
                        <th class="px-4 py-3 text-right">Valor Unitário</th>
                        <th class="px-4 py-3 text-right">Valor Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($nfe->items as $item)
                        <tr class="hover:bg-muted">
                            <td class="px-4 py-3">
                                <a href="{{ route('products.show', $item->product_id) }}" class="font-medium text-foreground hover:underline">{{ $item->product?->name ?? '—' }}</a>
                                <span class="text-xs text-muted-foreground">({{ $item->product?->internal_code ?? '—' }})</span>
                            </td>
                            <td class="px-4 py-3">{{ $item->warehouse?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($item->quantity, 4, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($item->unit_value, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($item->total_value, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-4 text-center text-muted-foreground/70">Nenhum item encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
