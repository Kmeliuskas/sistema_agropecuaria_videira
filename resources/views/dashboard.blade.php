@extends('layouts.app')

@section('title', 'Painel — WMS')
@section('page_title', 'Painel')

@php
    $totalEntries = $movements30d['total_entries'] ?? 0;
    $totalExits = $movements30d['total_exits'] ?? 0;
    $barsMax = max($totalEntries, $totalExits, 1);

    // Calcula percentuais da Curva ABC
    $abcTotal = $abcCurve['total_value'] ?: 1;
    $aValue = $abcCurve['classes']['A']['value'] ?? 0;
    $bValue = $abcCurve['classes']['B']['value'] ?? 0;
    $cValue = $abcCurve['classes']['C']['value'] ?? 0;
    $aPct = round(($aValue / $abcTotal) * 100, 1);
    $bPct = round(($bValue / $abcTotal) * 100, 1);
    $cPct = round(($cValue / $abcTotal) * 100, 1);

    $donutStops = sprintf(
        '%s 0%% %s%%, %s %s%% %s%%, %s %s%% 100%%',
        '#ef4444', $aPct,
        '#f59e0b', $aPct, $aPct + $bPct,
        '#22c55e', $aPct + $bPct
    );
@endphp

@section('content')
<div class="space-y-6">
    {{-- Tempo real: re-renderiza os totais do painel ao vivo --}}
    <turbo-echo-stream-source type="private" channel="dashboard"></turbo-echo-stream-source>
    {{-- KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">Produtos ativos</p>
                    <p class="mt-1 text-2xl font-semibold text-foreground num">{{ $totals['products'] }}</p>
                </div>
                <span class="stat-icon bg-primary/15 text-primary">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">Almoxarifados</p>
                    <p class="mt-1 text-2xl font-semibold text-foreground num">{{ $totals['warehouses'] }}</p>
                </div>
                <span class="stat-icon bg-primary/15 text-primary">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V8l9-5 9 5v13M3 21h18M9 21v-6h6v6" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">Itens em estoque</p>
                    <p class="mt-1 text-2xl font-semibold text-foreground num">{{ number_format($totals['items_in_stock'], 0, ',', '.') }}</p>
                </div>
                <span class="stat-icon bg-success/15 text-success">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V8zm4 0v12m6-12v12" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">Valor em estoque</p>
                    <p class="mt-1 text-2xl font-semibold text-foreground num">R$ {{ number_format($totals['stock_value'], 2, ',', '.') }}</p>
                </div>
                <span class="stat-icon bg-amber-500/15 text-amber-600 dark:text-amber-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Movimentações (30d) — gráfico de barras --}}
        <div class="card p-5 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-foreground">
                    Movimentações (30 dias) — Até {{ $movements30d['period']['to'] ?? date('d/m/Y') }}
                </h2>
                <span class="badge-muted">{{ $totalEntries + $totalExits }} total</span>
            </div>
            <div class="relative h-48 w-full">
                <canvas id="movementsChart" class="h-full w-full"></canvas>
            </div>
        </div>

        {{-- Curva ABC — donut --}}
        <div class="card flex flex-col p-5">
            <h2 class="mb-4 font-semibold text-foreground">Curva ABC (por valor)</h2>
            <div class="flex flex-1 flex-col items-center justify-center gap-4">
                <div class="donut h-36 w-36" style="--donut-stops: {{ $donutStops }}">
                    <div class="donut-hole h-28 w-28">
                        <p class="text-lg font-semibold text-foreground num">{{ $aPct }}%</p>
                        <p class="text-xs text-muted-foreground">Classe A</p>
                    </div>
                </div>
                <div class="grid w-full grid-cols-3 gap-2 text-center">
                    <div>
                        <p class="text-sm font-semibold text-red-500 num">{{ $aPct }}%</p>
                        <p class="text-[11px] uppercase text-muted-foreground">A</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-amber-500 num">{{ $bPct }}%</p>
                        <p class="text-[11px] uppercase text-muted-foreground">B</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-green-500 num">{{ $cPct }}%</p>
                        <p class="text-[11px] uppercase text-muted-foreground">C</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Alertas de estoque --}}
        <div class="card">
            <div class="flex items-center justify-between border-b border-border px-5 py-4">
                <h2 class="font-semibold text-foreground">Alertas de estoque baixo</h2>
                <span class="badge-danger">{{ $stockAlerts['count'] }}</span>
            </div>
            <div class="p-5">
                @if (empty($stockAlerts['items']))
                    <p class="flex items-center gap-2 text-sm text-muted-foreground/70">
                        <svg class="h-4 w-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Nenhum produto abaixo do mínimo.
                    </p>
                @else
                    <ul class="divide-y divide-border">
                        @foreach ($stockAlerts['items'] as $item)
                            <li class="flex items-center justify-between py-2.5 text-sm">
                                <div>
                                    <p class="font-medium text-foreground">{{ $item['name'] }}</p>
                                    <p class="text-xs text-muted-foreground/70">{{ $item['code'] }}</p>
                                </div>
                                <span class="badge-danger">{{ number_format($item['current'], 0, ',', '.') }} / {{ number_format($item['min'], 0, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Solicitações pendentes --}}
        <div class="card">
            <div class="border-b border-border px-5 py-4">
                <h2 class="font-semibold text-foreground">Solicitações de material</h2>
            </div>
            <div class="p-5 text-sm">
                @php
                    $labels = ['pending' => 'Pendentes', 'separating' => 'Em separação', 'checking' => 'Em conferência'];
                    $statuses = $pendingRequests['by_status'] ?? [];
                    $statusLabels = [
                        'pendente' => 'Pendentes',
                        'em_separacao' => 'Em separação',
                        'em_conferencia' => 'Em conferência',
                        'entregue' => 'Entregues',
                        'cancelado' => 'Cancelados',
                    ];
                @endphp
                @if (empty($statuses))
                    <p class="text-muted-foreground/70">Nenhuma solicitação ativa.</p>
                @else
                    @foreach ($statuses as $status => $total)
                        <div class="flex items-center justify-between py-1.5">
                            <span class="text-muted-foreground">{{ $statusLabels[$status] ?? $status }}</span>
                            <span class="font-medium text-foreground num">{{ $total }}</span>
                        </div>
                    @endforeach
                @endif
                <div class="mt-3 border-t border-border pt-3">
                    <a href="{{ route('material-requests.index') }}" class="text-sm font-medium text-primary hover:underline">Ver todas →</a>
                </div>
            </div>
        </div>

        {{-- Produtos vencidos --}}
        <div class="card">
            <div class="border-b border-border px-5 py-4">
                <h2 class="font-semibold text-foreground">Produtos Vencidos</h2>
            </div>
            <div class="p-5 text-sm">
                @if (empty($expiredProducts['items']))
                    <p class="text-muted-foreground/70">Nenhum produto vencido.</p>
                @else
                    @foreach ($expiredProducts['items'] as $p)
                        <div class="py-1.5">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-foreground">{{ $p['name'] }}</span>
                                <span class="text-xs text-danger">{{ \Carbon\Carbon::parse($p['expiry_date'])->format('d/m/Y') }}</span>
                            </div>
                            <div class="text-xs text-muted-foreground">{{ $p['code'] }}</div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Produtos próximos ao vencimento --}}
        <div class="card">
            <div class="border-b border-border px-5 py-4">
                <h2 class="font-semibold text-foreground">Próximos ao Vencimento</h2>
            </div>
            <div class="p-5 text-sm">
                @if (empty($expiringProducts['items']))
                    <p class="text-muted-foreground/70">Nenhum produto próximo ao vencimento.</p>
                @else
                    @foreach ($expiringProducts['items'] as $p)
                        <div class="py-1.5">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-foreground">{{ $p['name'] }}</span>
                                <span class="badge-warning">{{ $p['days_left'] }} dia(s)</span>
                            </div>
                            <div class="text-xs text-muted-foreground">{{ $p['code'] }} · vence {{ \Carbon\Carbon::parse($p['expiry_date'])->format('d/m/Y') }}</div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Operacional --}}
        <div class="card">
            <div class="border-b border-border px-5 py-4">
                <h2 class="font-semibold text-foreground">Operacional</h2>
            </div>
            <div class="p-5 text-sm">
                <div class="flex items-center justify-between py-1.5">
                    <span class="text-muted-foreground">Inventários em andamento</span>
                    <span class="font-medium text-foreground num">{{ $activeInventories['count'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between py-1.5">
                    <span class="text-muted-foreground">Movimentações (30 dias)</span>
                    <span class="font-medium text-foreground num">{{ $totalEntries + $totalExits }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
