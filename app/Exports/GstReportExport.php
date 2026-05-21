<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GstReportExport implements FromArray, WithHeadings, WithStyles, WithColumnFormatting, ShouldAutoSize, WithCustomStartCell, WithEvents
{
    protected $data;
    protected $firmStateCode;
    protected $request;

    public function __construct($data, $firmStateCode, $request)
    {
        $this->data = $data;
        $this->firmStateCode = $firmStateCode;
        $this->request = $request;
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function headings(): array
    {
        return [
            'Sr No.',
            'Date',
            'Invoice No.',
            'Customer Name',
            'GSTIN No.',
            'State Code',
            'State',
            'Taxable Amount ₹',
            'CGST ₹',
            'SGST/UTGST ₹',
            'IGST ₹',
            'Final Invoice Value ₹',
            'JSON Status',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $totalTaxable = 0;
        $totalCgst = 0;
        $totalSgst = 0;
        $totalIgst = 0;
        $totalGrand = 0;

        foreach ($this->data as $index => $invoice) {
            $customer = $invoice->customer;
            $customerStateCode = $customer ? preg_replace('/\D/', '', $customer->pos_code ?: $customer->state_code) : '';
            $sameState = $customerStateCode === '' || $this->firmStateCode === '' || $customerStateCode === $this->firmStateCode;

            $taxable = (float) $invoice->taxable_amount;
            $cgst = (float) $invoice->total_cgst;
            $sgst = $sameState ? (float) $invoice->total_sgst : 0;
            $igst = $sameState ? 0 : (float) $invoice->total_sgst;
            $grandTotal = (float) $invoice->grand_total;

            $totalTaxable += $taxable;
            $totalCgst += $cgst;
            $totalSgst += $sgst;
            $totalIgst += $igst;
            $totalGrand += $grandTotal;

            $rows[] = [
                $index + 1,
                $invoice->invoice_date ? $invoice->invoice_date->format('d-m-Y') : '-',
                $invoice->invoice_no,
                $customer ? $customer->name : '-',
                $customer ? $customer->gstin : '-',
                $customer ? $customer->state_code : '-',
                $customer ? $customer->state : '-',
                $taxable,
                $cgst,
                $sgst,
                $igst,
                $grandTotal,
                $invoice->einvoice_status,
            ];
        }

        $rows[] = [
            '',
            '',
            '',
            '',
            '',
            '',
            'Total',
            $totalTaxable,
            $totalCgst,
            $totalSgst,
            $totalIgst,
            $totalGrand,
            '',
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        
        $sheet->getStyle('A6:M6')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '4F46E5'],
            ],
        ]);

        $sheet->getStyle("A$highestRow:M$highestRow")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'F3F4F6'],
            ],
        ]);

        $sheet->getStyle("A6:M$highestRow")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);

        return [];
    }

    public function columnFormats(): array
    {
        $indianCurrencyFormat = '[$₹-en-IN]#,##0.00;[Red]-[$₹-en-IN]#,##0.00';
        return [
            'H' => $indianCurrencyFormat,
            'I' => $indianCurrencyFormat,
            'J' => $indianCurrencyFormat,
            'K' => $indianCurrencyFormat,
            'L' => $indianCurrencyFormat,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:M1');
                $sheet->setCellValue('A1', 'SAGARDUTT PEPSI DISTRIBUTORS');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells('A2:M2');
                $sheet->setCellValue('A2', 'GST Report (B2B Only)');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $fromDate = $this->request->query('from_date', now()->toDateString());
                $toDate = $this->request->query('to_date', now()->toDateString());
                $sheet->mergeCells('A3:M3');
                $sheet->setCellValue('A3', "Date Range: $fromDate to $toDate");
                $sheet->getStyle('A3')->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $user = \Illuminate\Support\Facades\Auth::user();
                $generatedBy = $user ? (trim((string) ($user->name ?? '')) !== '' ? (string) $user->name : (string) ($user->email ?? '')) : '';
                $now = now();
                $sheet->mergeCells('A4:M4');
                $sheet->setCellValue('A4', 'Generated On Date: ' . $now->toDateString() . '   Generated Time: ' . $now->format('H:i:s') . '   Generated By: ' . $generatedBy);
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            },
        ];
    }
}
