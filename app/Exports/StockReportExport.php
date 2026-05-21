<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StockReportExport implements FromArray, WithEvents, ShouldAutoSize
{
    public function __construct(
        private array $tableRows,
        private array $tableHeadings,
        private array $meta,
        private array $qtyCols,
        private array $currencyCols,
        private int $productNameCol
    ) {
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

                $headingsRow = 6;
                $firstDataRow = 7;
                $lastDataRow = $headingsRow + count($this->tableRows);
                $footerPreparedRow = $lastDataRow + 2;
                $footerSystemRow = $lastDataRow + 3;

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A3:{$lastCol}4")->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                $sheet->getStyle("A{$headingsRow}:{$lastCol}{$headingsRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E2E8F0']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                ]);

                if ($lastDataRow >= $headingsRow) {
                    $sheet->getStyle("A{$headingsRow}:{$lastCol}{$lastDataRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                    ]);
                }

                $sheet->freezePane("A{$firstDataRow}");

                $srCol = Coordinate::stringFromColumnIndex(1);
                $sheet->getColumnDimension($srCol)->setAutoSize(false);
                $sheet->getColumnDimension($srCol)->setWidth(9);
                $sheet->getStyle("{$srCol}{$firstDataRow}:{$srCol}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$srCol}{$firstDataRow}:{$srCol}{$lastDataRow}")->getNumberFormat()->setFormatCode('0');

                $prodCol = Coordinate::stringFromColumnIndex($this->productNameCol);
                $sheet->getColumnDimension($prodCol)->setAutoSize(false);
                $sheet->getColumnDimension($prodCol)->setWidth(40);
                $sheet->getStyle("{$prodCol}{$firstDataRow}:{$prodCol}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                foreach ($this->qtyCols as $idx) {
                    $col = Coordinate::stringFromColumnIndex((int) $idx);
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                foreach ($this->currencyCols as $idx) {
                    $col = Coordinate::stringFromColumnIndex((int) $idx);
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")
                        ->getNumberFormat()
                        ->setFormatCode('"₹" #,##,##0');
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                if ($lastDataRow >= $firstDataRow) {
                    $sheet->getStyle("A{$lastDataRow}:{$lastCol}{$lastDataRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F1F5F9']],
                        'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']]],
                    ]);
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
