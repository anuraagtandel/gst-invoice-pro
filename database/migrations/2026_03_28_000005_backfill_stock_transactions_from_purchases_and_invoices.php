<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('stock_transactions')
            ->whereIn('reference_type', ['purchase', 'invoice'])
            ->delete();

        $now = now();

        $purchases = DB::table('purchases')
            ->whereNull('deleted_at')
            ->select('id', 'bill_no', 'bill_date')
            ->get();

        foreach ($purchases as $purchase) {
            $rows = DB::table('purchase_items')
                ->where('purchase_id', $purchase->id)
                ->select('product_id', 'total_units')
                ->get()
                ->map(function ($pi) use ($purchase, $now) {
                    $units = (int) round((float) ($pi->total_units ?? 0));
                    if ($units <= 0) {
                        return null;
                    }
                    return [
                        'product_id' => $pi->product_id,
                        'type' => 'purchase',
                        'quantity_in_units' => $units,
                        'notes' => 'Purchase ' . ($purchase->bill_no ?? ''),
                        'transaction_date' => $purchase->bill_date,
                        'reference_type' => 'purchase',
                        'reference_id' => $purchase->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })
                ->filter()
                ->values()
                ->all();

            if (!empty($rows)) {
                DB::table('stock_transactions')->insert($rows);
            }
        }

        $invoices = DB::table('invoices')
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->select('id', 'invoice_no', 'invoice_date')
            ->get();

        foreach ($invoices as $invoice) {
            $rows = DB::table('invoice_items')
                ->where('invoice_id', $invoice->id)
                ->select('product_id', 'total_units')
                ->get()
                ->map(function ($ii) use ($invoice, $now) {
                    $units = (int) round((float) ($ii->total_units ?? 0));
                    if ($units <= 0) {
                        return null;
                    }
                    return [
                        'product_id' => $ii->product_id,
                        'type' => 'sale',
                        'quantity_in_units' => $units,
                        'notes' => 'Invoice ' . ($invoice->invoice_no ?? ''),
                        'transaction_date' => $invoice->invoice_date,
                        'reference_type' => 'invoice',
                        'reference_id' => $invoice->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })
                ->filter()
                ->values()
                ->all();

            if (!empty($rows)) {
                DB::table('stock_transactions')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        DB::table('stock_transactions')
            ->whereIn('reference_type', ['purchase', 'invoice'])
            ->delete();
    }
};

