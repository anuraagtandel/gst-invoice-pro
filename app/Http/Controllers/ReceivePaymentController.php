<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Services\InvoicePaymentSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReceivePaymentController extends Controller
{
    private function validationErrorResponse(Request $request, array $errors)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $errors,
            ], 422);
        }

        return back()->withInput()->withErrors($errors);
    }

    public function create(Request $request)
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $selectedCustomerId = $request->query('customer_id');
        $selectedInvoiceId = $request->query('invoice_id');

        return view('payments.receive', [
            'customers' => $customers,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedInvoiceId' => $selectedInvoiceId,
        ]);
    }

    public function pendingInvoices(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $invoices = Invoice::query()
            ->accountingActive()
            ->where('customer_id', $validated['customer_id'])
            ->where('payment_type', 'Credit')
            ->where('pending_amount', '>', 0)
            ->orderBy('invoice_date')
            ->get([
                'id',
                'invoice_no',
                'invoice_date',
                'due_date',
                'total_amount',
                'paid_amount',
                'pending_amount',
                'payment_status',
            ]);

        return response()->json([
            'invoices' => $invoices,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'payment_date' => 'required|date',
            'payment_mode' => 'required|in:Cash,UPI,Cheque,Bank,Other',
            'reference_no' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.000001|decimal:0,6',
            'allocations' => 'nullable|array',
            'allocations.*.invoice_id' => 'required|exists:invoices,id',
            'allocations.*.amount' => 'required|numeric|min:0|decimal:0,6',
        ]);

        $customerId = (int) $validated['customer_id'];
        $paymentAmount = round((float) $validated['amount'], 6);
        $paymentDate = Carbon::parse($validated['payment_date'])->toDateString();

        $rawAllocations = $validated['allocations'] ?? [];
        $allocMap = [];
        foreach ($rawAllocations as $row) {
            $invId = (int) ($row['invoice_id'] ?? 0);
            $amt = round((float) ($row['amount'] ?? 0), 6);
            if ($invId > 0 && $amt > 0) {
                $allocMap[$invId] = ($allocMap[$invId] ?? 0) + $amt;
            }
        }

        $totalAllocated = round(array_sum($allocMap), 6);
        if ($totalAllocated > $paymentAmount + 0.0000001) {
            return $this->validationErrorResponse($request, [
                'amount' => 'Allocated amount cannot exceed payment amount.',
            ]);
        }

        if (!empty($allocMap)) {
            $ids = array_keys($allocMap);
            $validInvoices = Invoice::query()
                ->accountingActive()
                ->where('customer_id', $customerId)
                ->where('payment_type', 'Credit')
                ->whereIn('id', $ids)
                ->get(['id', 'pending_amount'])
                ->keyBy('id');

            $validIds = array_map('intval', array_keys($validInvoices->all()));
            sort($ids);
            sort($validIds);
            if ($ids !== $validIds) {
                return $this->validationErrorResponse($request, [
                    'allocations' => 'Some selected invoices are invalid for this customer.',
                ]);
            }

            foreach ($allocMap as $invoiceId => $alloc) {
                $inv = $validInvoices->get((int) $invoiceId);
                $pending = round((float) ($inv?->pending_amount ?? 0), 6);
                if (round((float) $alloc, 6) > $pending + 0.0000001) {
                    return $this->validationErrorResponse($request, [
                        'allocations' => 'Allocation cannot exceed invoice pending.',
                    ]);
                }
            }
        }

        DB::transaction(function () use ($validated, $customerId, $paymentAmount, $paymentDate, $allocMap) {
            $payment = CustomerPayment::create([
                'customer_id' => $customerId,
                'payment_date' => $paymentDate,
                'amount' => $paymentAmount,
                'payment_mode' => $validated['payment_mode'],
                'reference_no' => $validated['reference_no'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoices = Invoice::query()
                ->accountingActive()
                ->where('customer_id', $customerId)
                ->where('payment_type', 'Credit')
                ->where('pending_amount', '>', 0)
                ->orderBy('invoice_date')
                ->lockForUpdate()
                ->get();

            $remaining = $paymentAmount;
            $touchedInvoiceIds = [];
            $invoiceById = $invoices->keyBy('id');
            $pendingById = [];
            foreach ($invoices as $inv) {
                $pendingById[(int) $inv->id] = round((float) ($inv->pending_amount ?? 0), 6);
            }

            if (!empty($allocMap)) {
                foreach ($allocMap as $invoiceId => $alloc) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $invId = (int) $invoiceId;
                    $inv = $invoiceById->get($invId);
                    if (!$inv) {
                        continue;
                    }
                    $pending = round((float) ($pendingById[$invId] ?? 0), 6);
                    if ($pending <= 0) {
                        continue;
                    }

                    $use = min($alloc, $remaining, $pending);
                    $use = round((float) $use, 6);
                    if ($use <= 0) {
                        continue;
                    }

                    $payment->allocations()->create([
                        'invoice_id' => $invId,
                        'amount' => $use,
                    ]);

                    $pendingById[$invId] = round($pending - $use, 6);
                    $remaining = round($remaining - $use, 6);
                    $touchedInvoiceIds[$invId] = true;
                }
            }

            foreach ($invoices as $inv) {
                if ($remaining <= 0) {
                    break;
                }
                $invId = (int) $inv->id;
                if (!empty($allocMap) && array_key_exists($invId, $allocMap)) {
                    continue;
                }
                $pending = round((float) ($pendingById[$invId] ?? 0), 6);
                if ($pending <= 0) {
                    continue;
                }

                $use = min($pending, $remaining);
                $use = round((float) $use, 6);
                if ($use <= 0) {
                    continue;
                }

                $payment->allocations()->create([
                    'invoice_id' => $invId,
                    'amount' => $use,
                ]);

                $pendingById[$invId] = round($pending - $use, 6);
                $remaining = round($remaining - $use, 6);
                $touchedInvoiceIds[$invId] = true;
            }

            $invoiceIds = array_keys($touchedInvoiceIds);
            if (!empty($invoiceIds)) {
                app(InvoicePaymentSyncService::class)->syncInvoices($invoiceIds, $customerId);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment received and allocated successfully.',
            ]);
        }

        return redirect()->route('app.receivables.index')->with('success', 'Payment received and allocated successfully.');
    }
}
