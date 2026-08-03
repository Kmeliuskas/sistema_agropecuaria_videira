@extends('layouts.app')

@section('title', 'Relatório de Comissões — WMS')
@section('page_title', 'Relatório de Comissões de Vendedores')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    {{-- Filtros --}}
    <form method="GET" class="grid grid-cols-1 gap-4 rounded-lg border border-border bg-surface p-4 sm:grid-cols-5 sm:items-end">
        <div>
            <label class="mb-1 block text-sm font-medium text-foreground">Data Inicial</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-foreground">Data Final</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-foreground">Vendedor</label>
            <select name="user_id" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                <option value="">todos</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2 flex gap-2">
            <button type="submit" class="btn-primary h-10 flex-1">Filtrar</button>
            <a href="{{ route('reports.commission') }}" class="btn-secondary h-10 flex-1">Limpar</a>
        </div>
    </form>

    {{-- Ações de exportação --}}
    <div class="flex justify-end gap-2">
        <a href="{{ route('reports.commission.csv', http_build_query(request()->except('page'))) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-medium text-primary hover:bg-primary hover:text-primary-foreground transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16v-4a4 4 0 014-4h2m-6 6h6m-6 0l3-3m-3 3l3 3" />
            </svg>
            CSV
        </a>
        <a href="{{ route('reports.commission.xlsx', http_build_query(request()->except('page'))) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-medium text-primary hover:bg-primary hover:text-primary-foreground transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v1m0 0a3 3 0 106 0m-6 0V9a3 3 0 016 0v8M9 7h6m-6 4h6m-6 4v4m6-4v4m-6-4h6" />
            </svg>
            XLSX
        </a>
        <a href="{{ route('reports.commission.pdf', http_build_query(request()->except('page'))) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-medium text-primary hover:bg-primary hover:text-primary-foreground transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a2 2 0 012 2v5a2 2 0 01-2 2h-1a2 2 0 01-2-2v-1m4-4h-4m4 0l-1-1m1 1" />
            </svg>
            PDF
        </a>
    </div>

    {{-- Resumo por vendedor --}}
    @if ($summary->isNotEmpty())
    <div class="overflow-x-auto rounded-lg border border-border bg-surface">
        <table class="w-full text-sm">
            <thead class="bg-muted">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-foreground">Vendedor</th>
                    <th class="px-4 py-2 text-center font-semibold text-foreground">Total de Vendas</th>
                    <th class="px-4 py-2 text-center font-semibold text-foreground">Qtde Total</th>
                    <th class="px-4 py-2 text-right font-semibold text-foreground">Valor Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @foreach ($summary as $s)
                <tr>
                    <td class="px-4 py-2">{{ $s->user->name ?? $s->employee->name ?? '-' }}</td>
                    <td class="px-4 py-2 text-center">{{ $s->total_sales }}</td>
                    <td class="px-4 py-2 text-center">{{ $s->total_quantity }}</td>
                    <td class="px-4 py-2 text-right">R$ {{ number_format((float) $s->total_value, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-muted">
                <tr>
                    <td colspan="2" class="px-4 py-2 font-semibold">Totais Gerais</td>
                    <td class="px-4 py-2 text-center font-semibold">{{ $summary->sum('total_quantity') }}</td>
                    <td class="px-4 py-2 text-right font-semibold">R$ {{ number_format($summary->sum('total_value'), 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    {{-- Lista de movimentações --}}
    <div class="overflow-x-auto rounded-lg border border-border bg-surface">
        <table class="w-full text-sm">
            <thead class="bg-muted">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-foreground">Data</th>
                    <th class="px-4 py-2 text-left font-semibold text-foreground">Vendedor</th>
                    <th class="px-4 py-2 text-left font-semibold text-foreground">Produto</th>
                    <th class="px-4 py-2 text-left font-semibold text-foreground">Código</th>
                    <th class="px-4 py-2 text-center font-semibold text-foreground">Qtde</th>
                    <th class="px-4 py-2 text-right font-semibold text-foreground">Custo Unit.</th>
                    <th class="px-4 py-2 text-right font-semibold text-foreground">Valor Total</th>
                    <th class="px-4 py-2 text-left font-semibold text-foreground">Almoxarifado</th>
                    <th class="px-4 py-2 text-left font-semibold text-foreground">Documento</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($movements as $m)
                <tr class="hover:bg-muted/50">
                    <td class="px-4 py-2">{{ $m->occurred_at ? \Carbon\Carbon::parse($m->occurred_at)->format('d/m/Y H:i') : '-' }}</td>
                    <td class="px-4 py-2">{{ $m->user->name ?? $m->employee->name ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $m->product->name ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $m->product->internal_code ?? '-' }}</td>
                    <td class="px-4 py-2 text-center">{{ $m->quantity }}</td>
                    <td class="px-4 py-2 text-right">R$ {{ number_format((float) ($m->unit_cost ?? 0), 4, ',', '.') }}</td>
                    <td class="px-4 py-2 text-right">R$ {{ number_format((float) $m->quantity * (float) ($m->unit_cost ?? 0), 2, ',', '.') }}</td>
                    <td class="px-4 py-2">{{ $m->warehouse->name ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $m->document_number ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-muted-foreground">Nenhuma saída encontrada para o período selecionado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginação --}}
    @if ($movements->hasPages())
    <div class="mt-4">
        {{ $movements->links() }}
    </div>
    @endif
</div>
@endsection