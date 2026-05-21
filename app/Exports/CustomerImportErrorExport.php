<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerImportErrorExport implements FromArray, WithHeadings
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return [
            'Business Name',
            'Mobile Number',
            'Customer Code',
            'Billing Address',
            'GSTIN',
            'City',
            'Area',
            'State',
            'POS Code (State Code)',
            'FSSAI No.',
            'Error Message',
        ];
    }

    public function array(): array
    {
        return array_map(function (array $r) {
            return [
                $r['name'] ?? '',
                $r['mobile'] ?? '',
                $r['code'] ?? '',
                $r['address'] ?? '',
                $r['gstin'] ?? '',
                $r['city'] ?? '',
                $r['area_name'] ?? '',
                $r['state'] ?? '',
                $r['pos_code'] ?? '',
                $r['fssai_no'] ?? '',
                $r['error_message'] ?? '',
            ];
        }, $this->rows);
    }
}

