@extends('layouts.app')

@section('title', 'Solicitação ' . $mr->code . ' — WMS')
@section('page_title', 'Solicitação ' . $mr->code)

@section('content')
<div class="space-y-6">
    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="{{ $mr->statusEnum()->badgeClass() }}">
                {{ $mr->statusEnum()->label() }}
            </span>
            <span class="text-sm text-muted-foreground">Solicitado por {{ $mr->requester->name ?? '—' }} em {{ $mr->created_at->format('d/m/Y H:i') }}</span>
        </div>

        @if ($isPending)
            @can('requests.approve')
            <div class="flex gap-2">
                <form method="POST" action="{{ route('material-requests.approve', $mr) }}">
                    @csrf
                    <button type="submit"
                        class="btn-success"
                        onclick="return confirm('Aprovar e liberar para separação?')">
                        Aprovar
                    </button>
                </form>
                <form method="POST" action="{{ route('material-requests.reject', $mr) }}">
                    @csrf
                    <button type="submit"
                        class="btn-danger-outline"
                        onclick="return confirm('Recusar esta solicitação?')">
                        Recusar
                    </button>
                </form>
            </div>
            @endcan
        @elseif ($canDeliver)
            @can('requests.deliver')
            <div class="flex gap-2">
                <form method="POST" action="{{ route('material-requests.deliver', $mr) }}">
                    @csrf
                    <button type="submit"
                        class="btn-primary"
                        onclick="return confirm('Entregar o material e descontar do estoque? A solicitação será finalizada.')">
                        Entregar material
                    </button>
                </form>
            </div>
            @endcan
        @endif
    </div>

    {{-- Dados --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card p-5 lg:col-span-2">
            <h2 class="mb-4 font-semibold text-foreground">Itens</h2>
            <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-border text-left text-xs uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3">Produto</th>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Observação</th>
                        <th class="px-4 py-3 text-right">Solicitado</th>
                        <th class="px-4 py-3 text-right">Aprovado</th>
                        <th class="px-4 py-3 text-right">Entregue</th>
                        <th class="px-4 py-3 text-right">Estoque atual</th>
                        <th class="px-4 py-3 text-right">Almox.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($mr->items as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium text-foreground">{{ $item->product->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground/70">{{ $item->product->internal_code ?? '—' }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $item->observation ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($item->quantity_requested, 2, ',', '.') }} {{ $item->product->unit->symbol ?? '' }}</td>
                            <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($item->quantity_approved, 2, ',', '.') }} {{ $item->product->unit->symbol ?? '' }}</td>
                            <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($item->quantity_delivered, 2, ',', '.') }} {{ $item->product->unit->symbol ?? '' }}</td>
                            <td id="product_{{ $item->product_id }}" class="px-4 py-3 text-right text-foreground">{{ number_format($item->product->current_stock, 0, ',', '.') }} {{ $item->product->unit->symbol ?? '' }}</td>
                            <td class="px-4 py-3 text-right text-muted-foreground">{{ $item->warehouse->name ?? '—' }}</td>
                            <turbo-echo-stream-source type="private" channel="stock.{{ $item->product_id }}"></turbo-echo-stream-source>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card p-5">
                <h2 class="mb-3 font-semibold text-foreground">Detalhes</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Código</dt>
                        <dd class="font-medium text-foreground">{{ $mr->code }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Solicitante</dt>
                        <dd class="text-foreground">{{ $mr->requester->name ?? '—' }}</dd>
                    </div>
                    @if ($mr->sector)
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Setor</dt>
                            <dd class="text-foreground">{{ $mr->sector->name }}{{ $mr->sector->code ? ' (' . $mr->sector->code . ')' : '' }}</dd>
                        </div>
                    @endif
                    @if ($mr->approver)
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Aprovador</dt>
                            <dd class="text-foreground">{{ $mr->approver->name }}</dd>
                        </div>
                    @endif
                    @if ($mr->justification)
                        <div>
                            <dt class="text-muted-foreground">Justificativa</dt>
                            <dd class="mt-1 text-foreground">{{ $mr->justification }}</dd>
                        </div>
                    @endif
                    @if ($mr->delivered_at)
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Entregue em</dt>
                            <dd class="text-foreground">{{ $mr->delivered_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                    @if ($mr->finished_at)
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Finalizado em</dt>
                            <dd class="text-foreground">{{ $mr->finished_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
