<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CommissionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
{
    public function __construct(
        protected readonly mixed $movements,
    ) {}

    public function title(): string
    {
        return 'Comissões de Vendas';
    }

    public function headings(): array
    {
        return [
            'Data',
            'Vendedor',
            'Funcionário',
            'Produto',
            'Código',
            'Quantidade',
            'Custo Unitário',
            'Valor Total',
            'Almoxarifado',
            'Documento',
            'Observação',
        ];
    }

    /** @param mixed $movement */
    public function map($movement): array
    {
        $cost = (float) $movement->unit_cost;
        if ((!$cost || $cost <= 0) && $movement->product) {
            $cost = (float) $movement->product->average_cost;
        }

        return [
            \Carbon\Carbon::parse($movement->occurred_at)->format('d/m/Y H:i'),
            $movement->user->name ?? $movement->employee->name ?? '-',
            $movement->employee->name ?? '-',
            $movement->product->name ?? '-',
            $movement->product->internal_code ?? '-',
            (float) $movement->quantity,
            number_format($cost, 4, ',', '.'),
            number_format((float) $movement->quantity * $cost, 2, ',', '.'),
            $movement->warehouse->name ?? '-',
            $movement->document_number ?? '-',
            $movement->observation ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '495057']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Data
            'B' => 25, // Vendedor
            'C' => 25, // Funcionário
            'D' => 35, // Produto
            'E' => 15, // Código
            'F' => 12, // Quantidade
            'G' => 15, // Custo Unitário
            'H' => 15, // Valor Total
            'I' => 20, // Almoxarifado
            'J' => 15, // Documento
            'K' => 30, // Observação
        ];
    }

    public function collection()
    {
        // Retorna a coleção já processada pelo controller
        if (is_object($this->movements) && method_exists($this->movements, 'getCollection')) {
            return $this->movements->getCollection();
        }
        return $this->movements;
    }
}
