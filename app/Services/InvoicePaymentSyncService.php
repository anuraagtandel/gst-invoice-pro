<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InvoicePaymentSyncService
{
    private function totalExprSql(string $tableAlias = 'invoices'): string
    {
        return "CASE WHEN COALESCE({$tableAlias}.total_amount, 0) > 0 THEN {$tableAlias}.total_amount ELSE COALESCE({$tableAlias}.grand_total, 0) END";
    }

    public function syncInvoices(array $invoiceIds, ?int $customerId = null): int
    {
        $invoiceIds = array_values(array_filter(array_unique(array_map('intval', $invoiceIds)), fn ($id) => $id > 0));
        if (empty($invoiceIds)) {
            return 0;
        }

        $allocSub = DB::table('customer_payment_allocations as a')
            ->join('customer_payments as p', 'a.customer_payment_id', '=', 'p.id')
            ->whereIn('a.invoice_id', $invoiceIds)
            ->when($customerId !== null, function ($q) use ($customerId) {
                $q->where('p.customer_id', $customerId);
            })
            ->selectRaw('a.invoice_id, COALESCE(SUM(a.amount), 0) as alloc_paid')
            ->groupBy('a.invoice_id');

        $totalExpr = $this->totalExprSql('invoices');
        $paidFromAllocExpr = "LEAST(({$totalExpr}), GREATEST(COALESCE(alloc.alloc_paid, 0), 0))";
        $paidSafeExpr = "LEAST(({$totalExpr}), GREATEST(COALESCE(invoices.paid_amount, 0), {$paidFromAllocExpr}))";
        $paidExpr = "CASE WHEN invoices.payment_type = 'Cash' THEN ({$totalExpr}) ELSE ({$paidSafeExpr}) END";
        $pendingExpr = "GREATEST(({$totalExpr}) - ({$paidExpr}), 0)";

        return DB::table('invoices')
            ->leftJoinSub($allocSub, 'alloc', function ($join) {
                $join->on('invoices.id', '=', 'alloc.invoice_id');
            })
            ->where('invoices.is_deleted', false)
            ->whereNull('invoices.deleted_at')
            ->whereIn('invoices.id', $invoiceIds)
            ->when($customerId !== null, function ($q) use ($customerId) {
                $q->where('invoices.customer_id', $customerId);
            })
            ->update([
                'invoices.total_amount' => DB::raw($totalExpr),
                'invoices.paid_amount' => DB::raw($paidExpr),
                'invoices.pending_amount' => DB::raw($pendingExpr),
                'invoices.payment_status' => DB::raw("CASE WHEN ({$paidExpr}) <= 0 THEN 'Unpaid' WHEN ({$pendingExpr}) <= 0 THEN 'Paid' ELSE 'Partial' END"),
            ]);
    }
}

