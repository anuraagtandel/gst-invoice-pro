<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $invoices;

    public function __construct($invoices)
    {
        $this->invoices = $invoices;
    }

    public function collection()
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return [
            'Invoice No',
            'Date',
            'Customer',
            'Salesman',
            'Taxable Amount',
            'Total GST',
            'Grand Total',
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_no,
            $invoice->invoice_date->format('d M, Y'),
            $invoice->customer->name ?? 'N/A',
            $invoice->salesman ?? '',
            number_format($invoice->taxable_amount, 2, '.', ''),
            number_format($invoice->total_cgst + $invoice->total_sgst + $invoice->total_cess, 2, '.', ''),
            number_format($invoice->grand_total, 2, '.', ''),
        ];
    }
}
