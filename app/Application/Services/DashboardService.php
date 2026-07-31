<?php

namespace App\Application\Services;

use App\Models\Inventory;
use App\Models\MaterialRequest;
use App\Models\Movement;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Caso de uso do Dashboard. Agrega KPIs operacionais para a tela inicial do
 * ERP/WMS: totais, estoque baixo, curva ABC de valor, inventários em andamento
 * e solicitações pendentes. Leituras apenas (sem escrita).
 */
class DashboardService
{
    public function summary(): array
    {
        return [
            'totals' => $this->totals(),
            'stock_alerts' => $this->stockAlerts(),
            'abc_curve' => $this->abcCurve(),
            'pending_requests' => $this->pendingRequests(),
            'active_inventories' => $this->activeInventories(),
            'movements_30d' => $this->recentMovements(),
        ];
    }

    protected function totals(): array
    {
        return [
            'products' => Product::where('active', true)->count(),
            'warehouses' => Warehouse::count(),
            'stock_value' => (float) Product::where('active', true)
                ->sum(DB::raw('current_stock * average_cost')),
            'items_in_stock' => (float) StockBalance::sum('current'),
        ];
    }

    /**
     * Produtos abaixo do estoque mínimo (regra de reposição).
     */
    protected function stockAlerts(): array
    {
        $low = Product::where('active', true)
            ->whereColumn('current_stock', '<', 'min_stock')
            ->orderBy('current_stock')
            ->limit(10)
            ->get(['id', 'internal_code', 'name', 'current_stock', 'min_stock', 'unit_id']);

        return [
            'count' => Product::where('active', true)
                ->whereColumn('current_stock', '<', 'min_stock')
                ->count(),
            'items' => $low->map(fn ($p) => [
                'id' => $p->id,
                'code' => $p->internal_code,
                'name' => $p->name,
                'current' => $p->current_stock,
                'min' => $p->min_stock,
            ])->all(),
        ];
    }

    /**
     * Curva ABC por valor de estoque (current * average_cost):
     * A = top 80%, B = 80-95%, C = restante.
     */
    protected function abcCurve(): array
    {
        $rows = Product::where('active', true)
            ->where('current_stock', '>', 0)
            ->get(['id', 'name', 'current_stock', 'average_cost'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'value' => (float) $p->current_stock * (float) $p->average_cost,
            ])
            ->sortByDesc('value')
            ->values();

        $total = $rows->sum('value') ?: 0;
        $accum = 0;
        $curve = ['A' => 0.0, 'B' => 0.0, 'C' => 0.0];
        $counts = ['A' => 0, 'B' => 0, 'C' => 0];

        foreach ($rows as $r) {
            $accum += $r['value'];
            $pct = $total > 0 ? ($accum / $total) * 100 : 0;
            $class = $pct <= 80 ? 'A' : ($pct <= 95 ? 'B' : 'C');
            $curve[$class] += $r['value'];
            $counts[$class]++;
        }

        return [
            'total_value' => round($total, 2),
            'classes' => [
                'A' => ['value' => round($curve['A'], 2), 'count' => $counts['A']],
                'B' => ['value' => round($curve['B'], 2), 'count' => $counts['B']],
                'C' => ['value' => round($curve['C'], 2), 'count' => $counts['C']],
            ],
        ];
    }

    protected function pendingRequests(): array
    {
        return [
            'count' => MaterialRequest::whereNotIn('status', ['finalizado', 'cancelado'])->count(),
            'by_status' => MaterialRequest::whereNotIn('status', ['finalizado', 'cancelado'])
                ->groupBy('status')
                ->select('status', DB::raw('count(*) as total'))
                ->pluck('total', 'status')
                ->all(),
        ];
    }

    protected function activeInventories(): array
    {
        return [
            'count' => Inventory::whereIn('status', ['draft', 'in_progress'])->count(),
            'items' => Inventory::whereIn('status', ['draft', 'in_progress'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'code', 'type', 'status', 'items_count', 'counted_count'])
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'code' => $i->code,
                    'type' => $i->type,
                    'status' => $i->status,
                    'progress' => $i->progress(),
                ])->all(),
        ];
    }

    protected function recentMovements(): array
    {
        $from = now()->subDays(30)->startOfDay();
        $to = now()->endOfDay();

        // Agrupa movimentações por dia para exibir no gráfico de barras
        $entriesByDay = Movement::where('type', 'entry')
            ->where('occurred_at', '>=', $from)
            ->where('occurred_at', '<=', $to)
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $exitsByDay = Movement::where('type', 'exit')
            ->where('occurred_at', '>=', $from)
            ->where('occurred_at', '<=', $to)
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        // Gera todos os dias do período (30 dias)
        $days = [];
        $dates = [];
        $entries = [];
        $exits = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[] = now()->subDays($i)->format('d/m');
            $entries[] = $entriesByDay[$date]->total ?? 0;
            $exits[] = $exitsByDay[$date]->total ?? 0;
        }

        return [
            'entries' => $entries,
            'exits' => $exits,
            'dates' => $dates,
            'total_entries' => array_sum($entries),
            'total_exits' => array_sum($exits),
            'period' => [
                'from' => $from->format('d/m/Y'),
                'to' => now()->format('d/m/Y'),
            ],
        ];
    }
}
