<?php

namespace App\Http\Controllers\Web;

use App\Exports\CommissionExport;
use App\Http\Controllers\Controller;
use App\Models\Movement;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class CommissionReportController extends Controller
{
    /**
     * Exibe o formulário de filtro e a listagem de comissões.
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());
        $userId = $request->input('user_id');

        $query = Movement::query()
            ->where('type', 'exit')
            ->whereBetween('occurred_at', [
                $from . ' 00:00:00',
                $to . ' 23:59:59',
            ]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $movements = $query
            ->with(['user', 'employee', 'product', 'warehouse', 'supplier'])
            ->orderByDesc('occurred_at')
            ->paginate(20)
            ->withQueryString();

        // Ajusta o unit_cost com o average_cost do produto quando necessário
        $movements->getCollection()->each(function ($movement) {
            $cost = (float) $movement->unit_cost;
            if ((!$cost || $cost <= 0) && $movement->product) {
                $cost = (float) $movement->product->average_cost;
            }
            $movement->unit_cost = $cost;
        });

        $summary = $this->buildSummary($from, $to, $userId);
        $users = $this->salespeople();

        return view('reports.commission', [
            'from' => $from,
            'to' => $to,
            'userId' => $userId,
            'movements' => $movements,
            'summary' => $summary,
            'users' => $users,
        ]);
    }

    /**
     * Exporta para CSV.
     */
    public function exportCsv(Request $request)
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $data = $this->getData($request);
        $movements = $data['movements'];

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="relatorio-comissoes.csv"',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $handle = fopen('php://output', 'w');
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($handle, [
            'Data', 'Vendedor', 'Funcionário', 'Produto', 'Código',
            'Quantidade', 'Custo Unitário', 'Valor Total',
            'Almoxarifado', 'Documento', 'Observação',
        ]);

        foreach ($movements as $m) {
            fputcsv($handle, [
                \Carbon\Carbon::parse($m->occurred_at)->format('d/m/Y H:i'),
                $m->user->name ?? $m->employee->name ?? '-',
                $m->employee->name ?? '-',
                $m->product->name ?? '-',
                $m->product->internal_code ?? '-',
                $m->quantity,
                number_format((float) $m->unit_cost, 4, ',', '.'),
                number_format((float) ($m->quantity * $m->unit_cost), 2, ',', '.'),
                $m->warehouse->name ?? '-',
                $m->document_number ?? '-',
                $m->observation ?? '-',
            ]);
        }

        fclose($handle);

        return response()->stream($handle, 200, $headers);
    }

    /**
     * Exporta para XLSX.
     */
    public function exportXlsx(Request $request)
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $data = $this->getData($request);

        return Excel::download(new CommissionExport($data['movements']), 'relatorio-comissoes.xlsx');
    }

    /**
     * Exporta para PDF.
     */
    public function exportPdf(Request $request)
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $data = $this->getData($request);

        $pdf = Pdf::loadView('reports.commission-pdf', $data);
        return $pdf->download('relatorio-comissoes.pdf');
    }

    /**
     * Busca os dados do relatório (query + resumo + vendedores).
     */
    protected function getData(Request $request): array
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());
        $userId = $request->input('user_id');

        $movements = Movement::query()
            ->where('type', 'exit')
            ->whereBetween('occurred_at', [
                $from . ' 00:00:00',
                $to . ' 23:59:59',
            ])
            ->when($userId, fn ($q, $uid) => $q->where('user_id', $uid))
            ->with(['user', 'employee', 'product', 'warehouse'])
            ->orderByDesc('occurred_at')
            ->get()
            ->each(function ($movement) {
                $cost = (float) $movement->unit_cost;
                if ((!$cost || $cost <= 0) && $movement->product) {
                    $cost = (float) $movement->product->average_cost;
                }
                $movement->unit_cost = $cost;
            });

        return [
            'from' => $from,
            'to' => $to,
            'userId' => $userId,
            'movements' => $movements,
            'summary' => $this->buildSummary($from, $to, $userId),
            'totalValue' => $movements->sum(fn ($m) => $m->quantity * $m->unit_cost),
        ];
    }

    /**
     * Resumo por vendedor.
     */
    protected function buildSummary(string $from, string $to, ?int $userId = null)
    {
        $query = Movement::query()
            ->where('type', 'exit')
            ->whereBetween('occurred_at', [
                $from . ' 00:00:00',
                $to . ' 23:59:59',
            ])
            ->when($userId, fn ($q, $uid) => $q->where('user_id', $uid))
            ->leftJoin('products', 'movements.product_id', '=', 'products.id')
            ->selectRaw('movements.user_id, movements.employee_id, COUNT(*) as total_sales, SUM(movements.quantity) as total_quantity, SUM(movements.quantity * CASE WHEN COALESCE(movements.unit_cost, 0) > 0 THEN movements.unit_cost ELSE products.average_cost END) as total_value')
            ->with(['user', 'employee'])
            ->groupBy('movements.user_id', 'movements.employee_id')
            ->orderByDesc('total_value');

        return $query->get();
    }

    /**
     * Lista de vendedores (usuários com roles de vendedor/supervisor/admin).
     */
    protected function salespeople()
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['vendedor', 'supervisor', 'administrador']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
