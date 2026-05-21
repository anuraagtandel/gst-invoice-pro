<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'Business Name *',
            'Mobile Number *',
            'Customer Code *',
            'Billing Address *',
            'GSTIN *',
            'City *',
            'Area *',
            'State *',
            'POS Code (State Code) *',
            'FSSAI No.',
        ];
    }

    public function array(): array
    {
        return [[
            'PRIYANKA TRADERS',
            '9876543210',
            'CUST001',
            'SHOP NO 12, MAIN MARKET',
            '26ABCDE1234F1Z5',
            'SILVASSA',
            'TOKARKHADA',
            'DADRA AND NAGAR HAVELI AND DAMAN & DIU',
            '26',
            '12345678901234',
        ]];
    }
}

