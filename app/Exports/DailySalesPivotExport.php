<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DailySalesPivotExport implements FromArray, WithEvents, ShouldAutoSize
{
    private array $tableRows;
    private array $tableHeadings;
    private array $meta;
    private array $qtyCols;
    private array $currencyCols;

    public function __construct(array $tableRows, array $tableHeadings, array $meta, array $qtyCols, array $currencyCols)
    {
        $this->tableRows = $tableRows;
        $this->tableHeadings = $tableHeadings;
        $this->meta = $meta;
        $this->qtyCols = $qtyCols;
        $this->currencyCols = $currencyCols;
    }

    private function padRow(array $row, int $cols): array
    {
        $out = $row;
        $missing = $cols - count($out);
        if ($missing > 0) {
            $out = array_merge($out, array_fill(0, $missing, ''));
        }
        return array_slice($out, 0, $cols);
    }

    public function array(): array
    {
        $cols = count($this->tableHeadings);

        $data = [];
        $data[] = $this->padRow([(string) ($this->meta['company'] ?? '')], $cols);
        $data[] = $this->padRow([(string) ($this->meta['report'] ?? '')], $cols);
        $data[] = $this->padRow([(string) ($this->meta['date_range'] ?? '')], $cols);
        $data[] = $this->padRow([(string) ($this->meta['generated_line'] ?? '')], $cols);
        $data[] = $this->padRow([''], $cols);
        $data[] = $this->padRow($this->tableHeadings, $cols);

        foreach ($this->tableRows as $r) {
            $data[] = $this->padRow($r, $cols);
        }

        $data[] = $this->padRow([''], $cols);
        $data[] = $this->padRow(['Prepared By: _______________________'], $cols);
        $data[] = $this->padRow(['System Generated Report'], $cols);

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $cols = count($this->tableHeadings);
                $lastCol = Coordinate::stringFromColumnIndex(max(1, $cols));

                $companyRow = 1;
                $reportRow = 2;
                $dateRow = 3;
                $genRow = 4;
                $headingsRow = 6;
                $firstDataRow = 7;
                $totalRow = $headingsRow + count($this->tableRows);
                $footerPreparedRow = $totalRow + 2;
                $footerSystemRow = $totalRow + 3;

                $sheet->mergeCells("A{$companyRow}:{$lastCol}{$companyRow}");
                $sheet->mergeCells("A{$reportRow}:{$lastCol}{$reportRow}");

                $sheet->getStyle("A{$companyRow}:{$lastCol}{$companyRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A{$reportRow}:{$lastCol}{$reportRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A{$dateRow}:{$lastCol}{$genRow}")->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle("A{$headingsRow}:{$lastCol}{$headingsRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E2E8F0']],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                    ],
                ]);

                if ($totalRow >= $headingsRow) {
                    $sheet->getStyle("A{$headingsRow}:{$lastCol}{$totalRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                        ],
                    ]);
                }

                if ($totalRow >= $firstDataRow) {
                    $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F1F5F9']],
                        'borders' => [
                            'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']],
                            'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                        ],
                    ]);
                }

                $sheet->freezePane("A{$firstDataRow}");

                $srCol = Coordinate::stringFromColumnIndex(1);
                $productCol = Coordinate::stringFromColumnIndex(2);

                $sheet->getStyle("{$srCol}{$headingsRow}:{$srCol}{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$productCol}{$headingsRow}:{$productCol}{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getColumnDimension($srCol)->setAutoSize(false);
                $sheet->getColumnDimension($srCol)->setWidth(9);
                $sheet->getStyle("{$srCol}{$firstDataRow}:{$srCol}{$totalRow}")->getNumberFormat()->setFormatCode('0');

                foreach ($this->qtyCols as $idx) {
                    $col = Coordinate::stringFromColumnIndex((int) $idx);
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                foreach ($this->currencyCols as $idx) {
                    $col = Coordinate::stringFromColumnIndex((int) $idx);
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$totalRow}")->getNumberFormat()->setFormatCode('"₹" #,##0.00');
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getColumnDimension($col)->setAutoSize(false);
                    $sheet->getColumnDimension($col)->setWidth(18);
                }

                $sheet->getStyle("A{$footerPreparedRow}:{$lastCol}{$footerSystemRow}")->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
                $sheet->mergeCells("A{$footerPreparedRow}:{$lastCol}{$footerPreparedRow}");
                $sheet->mergeCells("A{$footerSystemRow}:{$lastCol}{$footerSystemRow}");
            },
        ];
    }
}
