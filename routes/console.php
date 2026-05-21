<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('accounts:audit {--json : Output as JSON}', function () {
    $eps = 0.000001;
    $base = "is_deleted = 0 AND deleted_at IS NULL";

    $one = function (string $sql) {
        return (int) (DB::selectOne($sql)->c ?? 0);
    };

    $counts = [
        'meta' => [
            'generated_at' => now()->toISOString(),
            'eps' => $eps,
            'invoice_active_filter' => $base,
        ],
        'invoices' => [
            'active_count' => $one("SELECT COUNT(*) c FROM invoices WHERE {$base}"),
            'zero_total_but_grand_total' => $one("SELECT COUNT(*) c FROM invoices WHERE {$base} AND COALESCE(total_amount,0)=0 AND COALESCE(grand_total,0)>0"),
            'paid_gt_total' => $one("SELECT COUNT(*) c FROM invoices WHERE {$base} AND COALESCE(paid_amount,0) > COALESCE(total_amount,0) + {$eps}"),
            'paid_lt_0' => $one("SELECT COUNT(*) c FROM invoices WHERE {$base} AND COALESCE(paid_amount,0) < -{$eps}"),
            'pending_negative' => $one("SELECT COUNT(*) c FROM invoices WHERE {$base} AND COALESCE(pending_amount,0) < -{$eps}"),
            'pending_mismatch_vs_calc' => $one("SELECT COUNT(*) c FROM invoices WHERE {$base} AND ABS(COALESCE(pending_amount,0) - GREATEST(COALESCE(total_amount,0) - LEAST(COALESCE(total_amount,0), GREATEST(COALESCE(paid_amount,0),0)), 0)) > {$eps}"),
            'cash_invoices_with_pending_gt_0' => $one("SELECT COUNT(*) c FROM invoices WHERE {$base} AND payment_type = 'Cash' AND COALESCE(pending_amount,0) > {$eps}"),
            'status_inconsistent_with_amounts' => $one("SELECT COUNT(*) c FROM invoices WHERE {$base} AND ( (payment_status = 'Paid' AND COALESCE(pending_amount,0) > {$eps}) OR (payment_status = 'Unpaid' AND COALESCE(paid_amount,0) > {$eps}) )"),
        ],
        'payments' => [
            'payments_count' => $one("SELECT COUNT(*) c FROM customer_payments"),
            'allocations_count' => $one("SELECT COUNT(*) c FROM customer_payment_allocations"),
            'orphan_alloc_missing_payment' => $one("SELECT COUNT(*) c FROM customer_payment_allocations a LEFT JOIN customer_payments p ON p.id=a.customer_payment_id WHERE p.id IS NULL"),
            'orphan_alloc_missing_invoice' => $one("SELECT COUNT(*) c FROM customer_payment_allocations a LEFT JOIN invoices i ON i.id=a.invoice_id WHERE i.id IS NULL"),
            'cross_customer_allocations' => $one("SELECT COUNT(*) c FROM customer_payment_allocations a JOIN customer_payments p ON p.id=a.customer_payment_id JOIN invoices i ON i.id=a.invoice_id WHERE p.customer_id <> i.customer_id"),
            'payments_overallocated_vs_payment_amount' => $one("SELECT COUNT(*) c FROM ( SELECT p.id, p.amount, COALESCE(SUM(a.amount),0) alloc_sum FROM customer_payments p LEFT JOIN customer_payment_allocations a ON a.customer_payment_id=p.id GROUP BY p.id, p.amount ) x WHERE x.alloc_sum > x.amount + {$eps}"),
            'payments_amount_gt_0_but_no_allocations' => $one("SELECT COUNT(*) c FROM ( SELECT p.id, p.amount, COALESCE(SUM(a.amount),0) alloc_sum FROM customer_payments p LEFT JOIN customer_payment_allocations a ON a.customer_payment_id=p.id GROUP BY p.id, p.amount ) x WHERE x.amount > {$eps} AND x.alloc_sum <= {$eps}"),
        ],
        'deletes' => [
            'soft_deleted_only' => $one("SELECT COUNT(*) c FROM invoices WHERE is_deleted=0 AND deleted_at IS NOT NULL"),
            'is_deleted_only' => $one("SELECT COUNT(*) c FROM invoices WHERE is_deleted=1 AND deleted_at IS NULL"),
            'both_deleted' => $one("SELECT COUNT(*) c FROM invoices WHERE is_deleted=1 AND deleted_at IS NOT NULL"),
        ],
    ];

    if ($this->option('json')) {
        $this->line(json_encode($counts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return;
    }

    $this->info('ACCOUNTS AUDIT (READ ONLY)');
    $this->line('Generated: ' . $counts['meta']['generated_at']);
    $this->line('Active invoices filter: ' . $counts['meta']['invoice_active_filter']);
    $this->line('');
    $this->comment('Invoices');
    foreach ($counts['invoices'] as $k => $v) {
        $this->line($k . '=' . $v);
    }
    $this->line('');
    $this->comment('Payments');
    foreach ($counts['payments'] as $k => $v) {
        $this->line($k . '=' . $v);
    }
    $this->line('');
    $this->comment('Deletes');
    foreach ($counts['deletes'] as $k => $v) {
        $this->line($k . '=' . $v);
    }
})->purpose('Read-only audit for invoices/payments/allocation consistency');

Artisan::command('accounts:orphan-allocations {--id= : Specific allocation id to inspect/fix} {--fix : Delete only confirmed orphan rows} {--json : Output as JSON}', function () {
    $idOpt = $this->option('id');
    $id = $idOpt !== null && $idOpt !== '' ? (int) $idOpt : null;
    $fix = (bool) $this->option('fix');

    $baseQuery = DB::table('customer_payment_allocations as a')
        ->leftJoin('customer_payments as p', 'p.id', '=', 'a.customer_payment_id')
        ->leftJoin('invoices as i', 'i.id', '=', 'a.invoice_id')
        ->where(function ($q) {
            $q->whereNull('p.id')->orWhereNull('i.id');
        })
        ->select([
            'a.id as allocation_id',
            'a.customer_payment_id',
            'a.invoice_id',
            'a.amount',
            'a.created_at',
            'a.updated_at',
        ])
        ->selectRaw('CASE WHEN p.id IS NULL THEN 1 ELSE 0 END as missing_payment')
        ->selectRaw('CASE WHEN i.id IS NULL THEN 1 ELSE 0 END as missing_invoice');

    if ($id !== null && $id > 0) {
        $baseQuery->where('a.id', $id);
    }

    $rows = $baseQuery->orderByDesc('a.id')->get()->map(function ($r) {
        return [
            'allocation_id' => (int) $r->allocation_id,
            'customer_payment_id' => (int) $r->customer_payment_id,
            'invoice_id' => (int) $r->invoice_id,
            'amount' => (string) $r->amount,
            'created_at' => (string) $r->created_at,
            'updated_at' => (string) $r->updated_at,
            'missing_payment' => (int) $r->missing_payment === 1,
            'missing_invoice' => (int) $r->missing_invoice === 1,
        ];
    })->values()->all();

    $result = [
        'meta' => [
            'generated_at' => now()->toISOString(),
            'filter_allocation_id' => $id,
            'fix' => $fix,
        ],
        'count' => count($rows),
        'rows' => $rows,
        'deleted_allocation_ids' => [],
    ];

    if ($fix) {
        if ($id === null || $id <= 0) {
            $this->error('Refusing to delete without --id=<allocation_id>.');
            return 1;
        }
        if (count($rows) !== 1) {
            $this->error('Refusing to delete: expected exactly 1 orphan row for the provided --id.');
            return 1;
        }

        DB::transaction(function () use (&$result, $id) {
            $deleted = DB::table('customer_payment_allocations')
                ->where('id', $id)
                ->delete();
            if ($deleted > 0) {
                $result['deleted_allocation_ids'] = [$id];
            }
        });
    }

    if ($this->option('json')) {
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return 0;
    }

    $this->info('ORPHAN ALLOCATIONS (READ ONLY unless --fix)');
    $this->line('Count: ' . $result['count']);
    foreach ($rows as $r) {
        $this->line(
            'allocation_id=' . $r['allocation_id']
            . ' payment_id=' . $r['customer_payment_id']
            . ' invoice_id=' . $r['invoice_id']
            . ' amount=' . $r['amount']
            . ' missing_payment=' . ($r['missing_payment'] ? 'yes' : 'no')
            . ' missing_invoice=' . ($r['missing_invoice'] ? 'yes' : 'no')
        );
    }
    if (!empty($result['deleted_allocation_ids'])) {
        $this->line('Deleted: ' . implode(',', $result['deleted_allocation_ids']));
    }
    return 0;
})->purpose('Inspect/delete orphan customer_payment_allocations rows (safe, requires --id + --fix)');
