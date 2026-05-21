<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')
            ->where('pos_code', '26')
            ->where('tax_type', 'CGST_SGST')
            ->update(['tax_type' => 'CGST_UTGST']);

        DB::table('invoices')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('customers.pos_code', '26')
            ->where('invoices.tax_type', 'CGST_SGST')
            ->update(['invoices.tax_type' => 'CGST_UTGST']);
    }

    public function down(): void
    {
        DB::table('customers')
            ->where('pos_code', '26')
            ->where('tax_type', 'CGST_UTGST')
            ->update(['tax_type' => 'CGST_SGST']);

        DB::table('invoices')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('customers.pos_code', '26')
            ->where('invoices.tax_type', 'CGST_UTGST')
            ->update(['invoices.tax_type' => 'CGST_SGST']);
    }
};

