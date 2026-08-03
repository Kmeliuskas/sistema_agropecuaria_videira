<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Comissões</title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
            @bottom-right {
                content: "Página " counter(page) " de " counter(pages);
                font-size: 9pt;
                color: #666;
            }
            @bottom-center {
                content: "{{ config('app.name', 'WMS') }}";
                font-size: 9pt;
                color: #666;
            }
        }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }
        h1 {
            font-size: 16pt;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5pt;
        }
        .subtitle {
            color: #7f8c8d;
            font-size: 10pt;
            margin-bottom: 15pt;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: 600;
            background: #e3f2fd;
            color: #1565c0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20pt;
        }
        th {
            background: #2c3e50;
            color: #fff;
            font-weight: 600;
            padding: 6pt 8pt;
            text-align: left;
            font-size: 9pt;
            border: 1px solid #34495e;
        }
        td {
            padding: 4pt 8pt;
            border: 1px solid #ddd;
            font-size: 9pt;
        }
        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        tbody tr:hover {
            background: #e3f2fd;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-row {
            font-weight: 700;
            background: #e8f5e9 !important;
        }
        .summary-box {
            background: #f5f5f5;
            border-radius: 6px;
            padding: 12pt;
            margin-bottom: 15pt;
        }
        .summary-box h3 {
            margin: 0 0 8pt 0;
            font-size: 11pt;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <h1>Relatório de Comissões de Vendas</h1>
    <div class="subtitle">
        <span class="badge">Período: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</span>
    </div>

    @if ($summary->isNotEmpty())
    <h2 style="font-size:12pt; color:#2c3e50; margin-bottom:8pt;">Resumo por Vendedor</h2>
    <table>
        <thead>
            <tr>
                <th width="35%">Vendedor</th>
                <th width="15%" class="text-center">Total de Vendas</th>
                <th width="15%" class="text-center">Qtde Total</th>
                <th width="20%" class="text-right">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary as $s)
            <tr>
                <td>{{ $s->user->name ?? $s->employee->name ?? '-' }}</td>
                <td class="text-center">{{ $s->total_sales }}</td>
                <td class="text-center">{{ number_format((float) $s->total_quantity, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format((float) $s->total_value, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="2">Totais Gerais</td>
                <td class="text-center">{{ $summary->sum('total_quantity') }}</td>
                <td class="text-right">R$ {{ number_format($summary->sum('total_value'), 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <h2 style="font-size:12pt; color:#2c3e50; margin-bottom:8pt;">Detalhamento de Saídas</h2>
    <table>
        <thead>
            <tr>
                <th width="10%">Data</th>
                <th width="15%">Vendedor</th>
                <th width="20%">Produto</th>
                <th width="5%">Código</th>
                <th width="7%" class="text-center">Qtde</th>
                <th width="10%" class="text-right">Custo Unit.</th>
                <th width="10%" class="text-right">Valor Total</th>
                <th width="10%">Almoxarifado</th>
                <th width="8%">Documento</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($movements as $m)
            <tr>
                <td>{{ \Carbon\Carbon::parse($m->occurred_at)->format('d/m/Y H:i') }}</td>
                <td>{{ $m->user->name ?? $m->employee->name ?? '-' }}</td>
                <td>{{ $m->product->name ?? '-' }}</td>
                <td>{{ $m->product->internal_code ?? '-' }}</td>
                <td class="text-center">{{ number_format((float) $m->quantity, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format((float) $m->unit_cost, 4, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format((float) ($m->quantity * $m->unit_cost), 2, ',', '.') }}</td>
                <td>{{ $m->warehouse->name ?? '-' }}</td>
                <td>{{ $m->document_number ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center" style="padding:20pt;">Nenhuma saída encontrada para o período selecionado.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="6">Valor Total Geral</td>
                <td class="text-right">R$ {{ number_format((float) $totalValue, 2, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary-box">
        <h3 style="margin:0;">Total de Saídas: {{ $movements->count() }} movimentação(s)</h3>
        <h3 style="margin:4pt 0 0 0;">Valor Total Geral: R$ {{ number_format((float) $totalValue, 2, ',', '.') }}</h3>
    </div>
</body>
</html>
