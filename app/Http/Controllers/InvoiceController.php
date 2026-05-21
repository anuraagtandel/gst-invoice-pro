<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Salesman;
use App\Helpers\AmountToWords;
use App\Models\StockTransaction;
use App\Helpers\GSTCalculator;
use App\Helpers\InvoiceNumberGenerator;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    private function salesmanContext(Request $request): array
    {
        $user = $request->user();
        $roleName = strtolower((string) ($user?->role?->name ?? ''));
        if ($roleName !== 'salesman') {
            abort(403);
        }

        $salesmanId = (int) ($user?->salesman_id ?? 0);
        $salesmanName = trim((string) ($user?->salesman?->name ?? ''));

        if ($salesmanName === '' && $salesmanId > 0) {
            $salesmanName = trim((string) Salesman::query()->where('id', $salesmanId)->value('name'));
        }

        return [$salesmanId, $salesmanName];
    }

    private function normalizeStateCode(?string $code): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $code);
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 1) {
            return '0' . $digits;
        }
        return substr($digits, 0, 2);
    }

    private function validateEInvoiceCompany(array $settings): array
    {
        $missing = [];
        $required = [
            'gst_legal_name' => 'Company Legal Name',
            'gst_trade_name' => 'Company Trade Name',
            'gst_gstin' => 'Company GSTIN',
            'gst_pan' => 'Company PAN Number',
            'gst_address_1' => 'Company Address',
            'gst_city' => 'Company City',
            'gst_state' => 'Company State',
            'gst_state_code' => 'Company State Code',
            'gst_pin_code' => 'Company PIN Code',
            'gst_phone' => 'Company Phone Number',
            'gst_email' => 'Company Email Address',
        ];
        foreach ($required as $key => $label) {
            $v = trim((string) ($settings[$key] ?? ''));
            if ($v === '') {
                $missing[] = 'Missing ' . $label;
            }
        }

        return $missing;
    }

    private function validateEInvoiceCustomer(Customer $customer): array
    {
        $missing = [];

        if (trim((string) $customer->name) === '') $missing[] = 'Missing Customer Business Name';
        if (trim((string) $customer->address) === '') $missing[] = 'Missing Customer Address';
        if (trim((string) $customer->city) === '') $missing[] = 'Missing Customer City';
        if (trim((string) $customer->state) === '') $missing[] = 'Missing Customer State';

        $stateCode = $this->normalizeStateCode($customer->state_code) ?: $this->normalizeStateCode($customer->pos_code);
        if (!$stateCode) $missing[] = 'Missing Customer State Code';

        if (!preg_match('/^\d{6}$/', (string) ($customer->pin_code ?? ''))) $missing[] = 'Missing Customer PIN Code';

        $pos = $this->normalizeStateCode($customer->pos_code);
        if (!$pos) $missing[] = 'Missing Customer Place of Supply';

        return $missing;
    }

    public function eInvoiceJson(Request $request, $id)
    {
        $invoiceQuery = Invoice::with(['customer', 'invoiceItems.product']);
        $user = $request->user();
        $roleName = strtolower((string) ($user?->role?->name ?? ''));
        if ($roleName === 'salesman') {
            $salesmanId = $user?->salesman_id;
            if ($salesmanId) {
                $invoiceQuery->where('salesman_id', $salesmanId);
            } else {
                $invoiceQuery->whereRaw('1=0');
            }
        }
        $invoice = $invoiceQuery->findOrFail($id);
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        $missing = [];
        $missing = array_merge($missing, $this->validateEInvoiceCompany($settings));
        if (!$invoice->customer) {
            $missing[] = 'Missing Customer';
        } else {
            $missing = array_merge($missing, $this->validateEInvoiceCustomer($invoice->customer));
        }

        $sellerStateCode = $this->normalizeStateCode($settings['gst_state_code'] ?? null);
        $buyerPos = $this->normalizeStateCode($invoice->customer?->pos_code);
        $buyerStateCode = $this->normalizeStateCode($invoice->customer?->state_code) ?: $buyerPos;
        $sameState = $sellerStateCode && $buyerStateCode && $sellerStateCode === $buyerStateCode;

        $items = [];
        $totalTaxable = 0.0;
        $totalCgst = 0.0;
        $totalSgst = 0.0;
        $totalIgst = 0.0;
        $totalInvoiceValue = 0.0;
        $slNo = 1;

        foreach ($invoice->invoiceItems as $idx => $it) {
            $isFree = (bool) ($it->is_free ?? false);

            $rowMissing = [];
            $product = $it->product;
            $productCode = trim((string) ($product?->product_code ?? ''));
            if ($productCode === '') $rowMissing[] = 'Missing Product Code';

            $desc = trim((string) ($it->product_description ?? ''));
            if ($desc === '') $rowMissing[] = 'Missing Product Description';

            $hsn = trim((string) ($it->hsn_code ?? ''));
            if ($hsn === '') $rowMissing[] = 'Missing Product HSN Code';

            $packSize = (int) ($product?->pack_size ?? 1);
            if ($packSize <= 0) $packSize = 1;
            $qtyUnits = ((float) ($it->qty_ct ?? 0) * $packSize) + (float) ($it->qty_un ?? 0);
            if ($qtyUnits <= 0) $rowMissing[] = 'Missing Product Quantity';

            $gstRate = 0.0;
            $tradePrice = 0.0;
            $taxable = 0.0;
            if (!$isFree) {
                $gstRate = $product?->gst_rate !== null ? (float) $product->gst_rate : ((float) ($it->cgst_rate ?? 0) + (float) ($it->sgst_rate ?? 0));
                if ($gstRate <= 0) $rowMissing[] = 'Missing Product GST %';

                $tradePrice = $product?->trade_price !== null ? (float) $product->trade_price : 0.0;
                if ($tradePrice <= 0) $rowMissing[] = 'Missing Product Trade Price';

                $taxable = (float) ($it->taxable ?? 0);
                if ($taxable <= 0) $rowMissing[] = 'Missing Product Taxable Amount';
            }

            if (!empty($rowMissing)) {
                foreach ($rowMissing as $m) {
                    $missing[] = $m . ' (Item ' . ($idx + 1) . ')';
                }
                continue;
            }

            $cgstAmt = 0.0;
            $sgstAmt = 0.0;
            $igstAmt = 0.0;
            $lineTotal = 0.0;
            if (!$isFree) {
                $gstAmt = $taxable * ($gstRate / 100);
                $halfGstAmt = $gstAmt / 2;
                $cgstAmt = $halfGstAmt;
                $sgstAmt = $sameState ? $halfGstAmt : 0.0;
                $igstAmt = $sameState ? 0.0 : $halfGstAmt;
                $lineTotal = $taxable + $cgstAmt + $sgstAmt + $igstAmt;

                $totalTaxable += $taxable;
                $totalCgst += $cgstAmt;
                $totalSgst += $sgstAmt;
                $totalIgst += $igstAmt;
                $totalInvoiceValue += $lineTotal;
            }

            $items[] = [
                'SlNo' => (string) $slNo,
                'PrdCd' => $productCode,
                'PrdDesc' => $desc,
                'HsnCd' => $hsn,
                'Qty' => round($qtyUnits, 2),
                'Unit' => 'NOS',
                'IsServc' => 'N',
                'UnitPrice' => $isFree ? 0 : ($qtyUnits > 0 ? round(round($taxable, 2) / $qtyUnits, 2) : 0),
                'TotAmt' => $isFree ? 0 : round($taxable, 2),
                'AssAmt' => $isFree ? 0 : round($taxable, 2),
                'GstRt' => $isFree ? 0 : round($gstRate, 2),
                'CgstAmt' => $isFree ? 0 : round($cgstAmt, 2),
                'SgstAmt' => $isFree ? 0 : round($sgstAmt, 2),
                'IgstAmt' => $isFree ? 0 : round($igstAmt, 2),
                'TotItemVal' => $isFree ? 0 : round($lineTotal, 2),
            ];
            $slNo++;
        }

        if (!empty($missing)) {
            $unique = array_values(array_unique($missing));
            return redirect()->route('app.invoices.edit', $invoice->id)->with('error', implode(' | ', $unique));
        }

        $customer = $invoice->customer;
        $buyerGstin = trim((string) ($customer->gstin ?? ''));
        $invType = $buyerGstin !== '' ? 'B2B' : 'B2C';

        $rawBuyerAddr = trim((string) ($customer->address ?? ''));
        if ($rawBuyerAddr === '') {
            $parts = [];
            $name = trim((string) ($customer->name ?? ''));
            $city = trim((string) ($customer->city ?? ''));
            if ($name !== '') {
                $parts[] = $name;
            }
            if ($city !== '') {
                $parts[] = $city;
            }
            $rawBuyerAddr = $parts !== [] ? implode(', ', $parts) : 'NA';
        }
        $buyerAddr1 = substr($rawBuyerAddr, 0, 100);
        if ($buyerAddr1 === '') {
            $buyerAddr1 = 'NA';
        }
        $buyerAddr2 = '';
        if (strlen($rawBuyerAddr) > 100) {
            $buyerAddr2 = substr($rawBuyerAddr, 100, 100);
        }
        $buyerAddr2 = trim($buyerAddr2);

        $buyerDtls = [
            'Gstin' => $buyerGstin !== '' ? $buyerGstin : 'URP',
            'LglNm' => (string) ($customer->name ?? ''),
            'TrdNm' => (string) ($customer->name ?? ''),
            'Addr1' => $buyerAddr1,
            'Loc' => (string) ($customer->city ?? ''),
            'Stcd' => (string) ($buyerStateCode ?? ''),
            'Pos' => (string) ($buyerPos ?? ''),
            'Pin' => (int) ($customer->pin_code ?? 0),
            'Ph' => (string) ($customer->mobile ?? ''),
            'Em' => (string) ($customer->email ?? ''),
        ];
        if (strlen($buyerAddr2) >= 3 && $buyerAddr2 !== '-' && strtoupper($buyerAddr2) !== 'NA') {
            $buyerDtls['Addr2'] = $buyerAddr2;
        }

        $invoiceDate = $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') : now()->format('d/m/Y');
        $docNo = (string) ($invoice->invoice_no ?? '');

        $payload = [
            'Version' => '1.1',
            'TranDtls' => [
                'TaxSch' => 'GST',
                'SupTyp' => $invType,
            ],
            'DocDtls' => [
                'Typ' => 'INV',
                'No' => $docNo,
                'Dt' => $invoiceDate,
            ],
            'SellerDtls' => [
                'Gstin' => (string) ($settings['gst_gstin'] ?? ''),
                'LglNm' => (string) ($settings['gst_legal_name'] ?? ''),
                'TrdNm' => (string) ($settings['gst_trade_name'] ?? ''),
                'Addr1' => (string) ($settings['gst_address_1'] ?? ''),
                'Addr2' => (string) ($settings['gst_address_2'] ?? ($settings['gst_address_1'] ?? '')),
                'Loc' => (string) ($settings['gst_city'] ?? ''),
                'Stcd' => (string) ($sellerStateCode ?? ''),
                'Pin' => (int) ($settings['gst_pin_code'] ?? 0),
                'Ph' => (string) ($settings['gst_phone'] ?? ''),
                'Em' => (string) ($settings['gst_email'] ?? ''),
            ],
            'BuyerDtls' => $buyerDtls,
            'ItemList' => $items,
            'ValDtls' => [
                'AssVal' => round($totalTaxable, 2),
                'CgstVal' => round($totalCgst, 2),
                'SgstVal' => round($totalSgst, 2),
                'IgstVal' => round($totalIgst, 2),
                'TotInvVal' => round((float) ($invoice->grand_total ?? $totalInvoiceValue), 2),
            ],
        ];

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $docNo);
        $safe = trim($safe, '-');
        if ($safe === '') {
            $safe = 'INVOICE';
        }
        $fileName = 'EInvoice_' . $safe . '.json';

        $invoice->update(['einvoice_status' => 'Downloaded']);

        return response(
            json_encode([$payload], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            200,
            [
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]
        );
    }

    private function creditLimitPolicy(): string
    {
        $p = strtolower(trim((string) Setting::get('credit_limit_policy', 'warn')));
        return in_array($p, ['warn', 'block'], true) ? $p : 'warn';
    }

    private function creditLimitSummary(Customer $customer, float $newPending, ?Invoice $existingInvoice = null): array
    {
        $limit = $customer->credit_limit === null ? null : round((float) $customer->credit_limit, 6);

        $outstanding = (float) Invoice::query()
            ->active()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->sum('pending_amount');

        if ($existingInvoice && (int) $existingInvoice->customer_id === (int) $customer->id) {
            $outstanding = $outstanding - (float) ($existingInvoice->pending_amount ?? 0);
        }

        $outstanding = round(max(0, $outstanding), 6);
        $newPending = round(max(0, $newPending), 6);
        $projected = round($outstanding + $newPending, 6);

        $exceeded = $limit !== null && $limit > 0 && $projected > ($limit + 0.0000001);

        return [
            'limit' => $limit,
            'outstanding' => $outstanding,
            'new_pending' => $newPending,
            'projected' => $projected,
            'exceeded' => $exceeded,
        ];
    }

    private function paymentFields(array $validated, float $grandTotal, ?float $existingPaidAmount = null): array
    {
        $paymentType = $validated['payment_type'] ?? (($validated['payment_mode'] ?? 'Cash') === 'Credit' ? 'Credit' : 'Cash');
        $paymentMode = $paymentType === 'Credit' ? 'Credit' : ($validated['payment_mode'] ?? 'Cash');

        $totalAmount = round($grandTotal, 6);

        if ($paymentType === 'Cash') {
            return [
                'payment_type' => 'Cash',
                'payment_mode' => $paymentMode,
                'due_date' => null,
                'payment_status' => 'Paid',
                'total_amount' => $totalAmount,
                'paid_amount' => $totalAmount,
                'pending_amount' => 0.0,
            ];
        }

        $incomingPaid = $validated['paid_amount'] ?? null;

        if ($incomingPaid !== null && $incomingPaid !== '') {
            $paidAmount = round((float) $incomingPaid, 6);
        } elseif ($existingPaidAmount !== null) {
            $paidAmount = round((float) $existingPaidAmount, 6);
        } else {
            $paidAmount = 0;
        }

        if ($paidAmount < 0) {
            $paidAmount = 0;
        }
        if ($paidAmount > $totalAmount) {
            $paidAmount = $totalAmount;
        }

        $pendingAmount = round($totalAmount - $paidAmount, 6);
        if ($pendingAmount < 0) {
            $pendingAmount = 0;
        }

        if ($paidAmount <= 0) {
            $paymentStatus = 'Unpaid';
        } elseif ($pendingAmount <= 0) {
            $paymentStatus = 'Paid';
        } else {
            $paymentStatus = 'Partial';
        }

        return [
            'payment_type' => $paymentType,
            'payment_mode' => $paymentMode,
            'due_date' => $validated['due_date'] ?? null,
            'payment_status' => $paymentStatus,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
        ];
    }

    public function index(Request $request)
    {
        $query = Invoice::active()->with('customer')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('salesman', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->paginate(20);
        return view('invoices.index', compact('invoices'));
    }

    public function salesmanIndex(Request $request)
    {
        [$salesmanId, $salesmanName] = $this->salesmanContext($request);
        if ($salesmanId <= 0 || $salesmanName === '') {
            return redirect()->route('app.dashboard')->with('error', 'Salesman user is not linked to any salesman. Please contact admin.');
        }

        $preset = (string) $request->query('preset', 'today');
        $fromRaw = (string) $request->query('from', '');
        $toRaw = (string) $request->query('to', '');

        $today = now()->startOfDay();
        if ($fromRaw === '' || $toRaw === '') {
            if ($preset === 'week') {
                $from = $today->copy()->startOfWeek();
                $to = $today->copy()->endOfDay();
            } elseif ($preset === 'month') {
                $from = $today->copy()->startOfMonth();
                $to = $today->copy()->endOfDay();
            } elseif ($preset === 'custom') {
                $from = $today->copy();
                $to = $today->copy()->endOfDay();
            } else {
                $preset = 'today';
                $from = $today->copy();
                $to = $today->copy()->endOfDay();
            }
        } else {
            try {
                $from = \Carbon\Carbon::parse($fromRaw)->startOfDay();
            } catch (\Throwable $e) {
                $from = $today->copy();
            }
            try {
                $to = \Carbon\Carbon::parse($toRaw)->endOfDay();
            } catch (\Throwable $e) {
                $to = $today->copy()->endOfDay();
            }
            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
        }

        $query = Invoice::active()->with('customer')->latest()
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()]);

        $lowerSalesmanName = strtolower($salesmanName);
        $query->where(function ($q) use ($salesmanId, $lowerSalesmanName) {
            $q->where('salesman_id', $salesmanId)
                ->orWhereRaw('LOWER(salesman) = ?', [$lowerSalesmanName]);
        });

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('salesman', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->paginate(20)->withQueryString();
        $salesmanFilterPreset = $preset;
        $salesmanFilterFrom = $from->toDateString();
        $salesmanFilterTo = $to->toDateString();
        return view('invoices.index', compact('invoices', 'salesmanFilterPreset', 'salesmanFilterFrom', 'salesmanFilterTo'));
    }

    public function salesmanCashSummary(Request $request): JsonResponse
    {
        [$salesmanId, $salesmanName] = $this->salesmanContext($request);

        $fromRaw = (string) $request->query('from', '');
        $toRaw = (string) $request->query('to', '');

        try {
            $from = $fromRaw !== '' ? \Carbon\Carbon::parse($fromRaw)->startOfDay() : now()->startOfDay();
        } catch (\Throwable $e) {
            $from = now()->startOfDay();
        }
        try {
            $to = $toRaw !== '' ? \Carbon\Carbon::parse($toRaw)->endOfDay() : now()->endOfDay();
        } catch (\Throwable $e) {
            $to = now()->endOfDay();
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $base = Invoice::active()->with('customer')->latest()
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()]);

        $lowerSalesmanName = strtolower($salesmanName);
        $base->where(function ($q) use ($salesmanId, $lowerSalesmanName) {
            $q->where('salesman_id', $salesmanId)
                ->orWhereRaw('LOWER(salesman) = ?', [$lowerSalesmanName]);
        });

        $cashFilter = function ($q) {
            $q->where('payment_type', 'Cash')
                ->orWhere(function ($y) {
                    $y->where(function ($z) {
                        $z->whereNull('payment_type')->orWhere('payment_type', '');
                    })->where('payment_mode', 'Cash');
                });
        };

        $creditFilter = function ($q) {
            $q->where('payment_type', 'Credit')
                ->orWhere(function ($y) {
                    $y->where(function ($z) {
                        $z->whereNull('payment_type')->orWhere('payment_type', '');
                    })->where('payment_mode', 'Credit');
                });
        };

        $cashInvoicesQuery = (clone $base)->where(function ($q) use ($cashFilter) {
            $cashFilter($q);
        });

        $cashCollected = (float) $cashInvoicesQuery->sum('paid_amount');
        $cashSales = (float) $cashInvoicesQuery->sum('grand_total');
        $cashCount = (int) $cashInvoicesQuery->count();
        $creditInvoicesQuery = (clone $base)->where(function ($q) use ($creditFilter) {
            $creditFilter($q);
        });
        $creditCount = (int) $creditInvoicesQuery->count();
        $creditSales = (float) $creditInvoicesQuery->sum('grand_total');
        $totalSales = (float) ($cashSales + $creditSales);

        return response()->json([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'cash_collected' => $cashCollected,
            'cash_sales' => $cashSales,
            'cash_invoices' => $cashCount,
            'credit_invoices' => $creditCount,
            'credit_sales' => $creditSales,
            'credit_collected' => $creditSales,
            'total_sales' => $totalSales,
        ]);
    }

    public function create(Request $request)
    {
        $lockedSalesmanName = null;
        if ($request->routeIs('salesman.*')) {
            [$salesmanId, $salesmanName] = $this->salesmanContext($request);
            if ($salesmanId <= 0 || $salesmanName === '') {
                return redirect()->route('app.dashboard')->with('error', 'Salesman user is not linked to any salesman. Please contact admin.');
            }
            $lockedSalesmanName = $salesmanName;
        }

        $customers = Customer::query()
            ->where('is_active', true)
            ->withSum(['invoices as outstanding_amount' => function ($q) {
                $q->accountingActive();
            }], 'pending_amount')
            ->get();
        $products = Product::where('is_active', true)->get();
        $nextInvoiceNo = InvoiceNumberGenerator::next();
        $schemes = \App\Models\Scheme::active()->with('schemeSlabs')->get();
        $salesmen = Salesman::where('is_active', true)->orderBy('name')->get();
        $creditLimitPolicy = $this->creditLimitPolicy();
        $groupSchemes = \App\Models\GroupScheme::query()
            ->where('is_active', true)
            ->with(['slabs', 'productGroup:id,name', 'freeProduct:id,name,volume,base_price,gst_rate,pack_size'])
            ->orderBy('name')
            ->get();
        $productGroupsPairs = \Illuminate\Support\Facades\DB::table('product_group_product')
            ->select(['product_id', 'product_group_id'])
            ->get();
        
        return view('invoices.create', compact('customers', 'products', 'nextInvoiceNo', 'schemes', 'creditLimitPolicy', 'salesmen', 'groupSchemes', 'productGroupsPairs', 'lockedSalesmanName'));
    }

    public function store(StoreInvoiceRequest $request)
    {
        $validated = $request->validated();
        $forceSalesmanId = null;
        $forceSalesmanName = null;
        $createdInvoiceId = null;
        if ($request->routeIs('salesman.*')) {
            [$forceSalesmanId, $forceSalesmanName] = $this->salesmanContext($request);
            if (($forceSalesmanId ?? 0) <= 0 || trim((string) $forceSalesmanName) === '') {
                return back()->with('error', 'Salesman user is not linked to any salesman. Please contact admin.')->withInput();
            }
            $validated['salesman'] = $forceSalesmanName;
        }

        // Stock validation check
        foreach ($validated['items'] as $item) {
            $product = Product::findOrFail($item['product_id']);
            $totalUnitsNeeded = ($item['qty_ct'] ?? 0) * $product->pack_size + ($item['qty_un'] ?? 0);

            if ($totalUnitsNeeded > $product->current_stock_units) {
                return back()->with('error', "Insufficient stock for {$product->name}. Required: {$totalUnitsNeeded} units, Available: {$product->current_stock_units} units.")->withInput();
            }
        }

        $warning = null;

        try {
            DB::transaction(function () use ($validated, &$warning, $forceSalesmanId, &$createdInvoiceId) {
                $customer = Customer::findOrFail($validated['customer_id']);
                $salesman = $validated['salesman'] ?? Setting::get('default_salesman', '');
                $salesmanId = $forceSalesmanId ? (int) $forceSalesmanId : null;
                if (!$salesmanId && is_string($salesman) && trim($salesman) !== '') {
                    $salesmanId = Salesman::query()
                        ->whereRaw('LOWER(name) = ?', [strtolower(trim($salesman))])
                        ->value('id');
                }
            
                $invoiceItemsData = [];
                $processedItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::with(['schemes.schemeSlabs'])->findOrFail($item['product_id']);
                
                $inputForCalc = [
                    'base_price' => $product->base_price,
                    'qty_ct' => $item['qty_ct'] ?? 0,
                    'qty_un' => $item['qty_un'] ?? 0,
                    'pack_size' => $product->pack_size,
                    'discount_pct' => $item['discount_pct'] ?? 0,
                    'gst_rate' => $product->gst_rate,
                    'is_free' => $item['is_free'] ?? false,
                    'mrp' => $product->mrp,
                ];

                $calcResult = GSTCalculator::calcItem($inputForCalc, $customer->tax_type, []);
                $processedItems[] = $calcResult;

                $invoiceItemsData[] = [
                    'product_id' => $product->id,
                    'is_free' => $inputForCalc['is_free'],
                    'product_description' => $product->name . ' ' . $product->volume,
                    'hsn_code' => $product->hsn_code,
                    'mrp' => $product->mrp,
                    'qty_ct' => $inputForCalc['qty_ct'],
                    'qty_un' => $inputForCalc['qty_un'],
                    'total_units' => $calcResult['total_units'],
                    'discount_pct' => $inputForCalc['discount_pct'],
                    'base_price' => $product->base_price,
                    'taxable' => $calcResult['taxable'],
                    'cgst_rate' => $calcResult['cgst_rate'],
                    'cgst' => $calcResult['cgst'],
                    'sgst_rate' => $calcResult['sgst_rate'],
                    'sgst' => $calcResult['sgst'],
                    'cess_rate' => 0,
                    'cess' => 0,
                    'line_total' => $calcResult['line_total'],
                ];
            }

                $totals = GSTCalculator::calcInvoiceTotals($processedItems);
                $amountInWords = AmountToWords::convert($totals['grand_total']);
                $invoiceNo = InvoiceNumberGenerator::next($validated['invoice_date']);
                $paymentFields = $this->paymentFields($validated, (float) $totals['grand_total']);

                $limitSummary = $this->creditLimitSummary($customer, (float) $paymentFields['pending_amount']);
                if ($limitSummary['exceeded']) {
                    $msg = "Credit limit exceeded for {$customer->name}. Outstanding: {$limitSummary['outstanding']}, New Pending: {$limitSummary['new_pending']}, Projected: {$limitSummary['projected']}, Limit: {$limitSummary['limit']}.";
                    if ($this->creditLimitPolicy() === 'block') {
                        throw new \RuntimeException($msg);
                    }
                    $warning = $msg;
                }

                $invoice = Invoice::create(array_merge([
                'invoice_no' => $invoiceNo,
                'invoice_date' => $validated['invoice_date'],
                'customer_id' => $customer->id,
                'payment_mode' => $paymentFields['payment_mode'],
                'payment_type' => $paymentFields['payment_type'],
                'due_date' => $paymentFields['due_date'],
                'payment_status' => $paymentFields['payment_status'],
                'salesman' => $salesman,
                'salesman_id' => $salesmanId,
                'po_no' => $validated['po_no'] ?? null,
                'tax_type' => $customer->tax_type,
                'amount_in_words' => $amountInWords,
                'is_deleted' => false,
                'total_amount' => $paymentFields['total_amount'],
                'paid_amount' => $paymentFields['paid_amount'],
                'pending_amount' => $paymentFields['pending_amount'],
                ], $totals));
                $createdInvoiceId = (int) $invoice->id;

            $invoice->invoiceItems()->createMany($invoiceItemsData);

            $stockTx = [];
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $units = ((float) ($item['qty_ct'] ?? 0) * (int) $product->pack_size) + (float) ($item['qty_un'] ?? 0);
                if ($units <= 0) {
                    continue;
                }
                $stockTx[] = [
                    'product_id' => $product->id,
                    'type' => 'sale',
                    'quantity_in_units' => (int) round($units),
                    'notes' => 'Invoice ' . $invoice->invoice_no,
                    'transaction_date' => $invoice->invoice_date,
                    'reference_type' => 'invoice',
                    'reference_id' => $invoice->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($stockTx)) {
                StockTransaction::insert($stockTx);
            }
            
                InvoiceNumberGenerator::increment($validated['invoice_date']);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $redirectRoute = $request->routeIs('salesman.*') ? 'salesman.invoices.index' : 'app.invoices.index';
        $redirect = redirect()->route($redirectRoute)->with('success', 'Invoice created successfully.');
        if ($request->routeIs('salesman.*') && $createdInvoiceId) {
            $redirect = $redirect->with('created_invoice_id', $createdInvoiceId);
        }
        if ($warning) {
            $redirect = $redirect->with('warning', $warning);
        }
        return $redirect;
    }

    public function edit($id)
    {
        $invoiceQuery = Invoice::with(['customer', 'invoiceItems']);
        $user = request()->user();
        $roleName = strtolower((string) ($user?->role?->name ?? ''));
        if ($roleName === 'salesman') {
            $salesmanId = $user?->salesman_id;
            if ($salesmanId) {
                $invoiceQuery->where('salesman_id', $salesmanId);
            } else {
                $invoiceQuery->whereRaw('1=0');
            }
        }
        $invoice = $invoiceQuery->findOrFail($id);
        $customers = Customer::query()
            ->where('is_active', true)
            ->withSum(['invoices as outstanding_amount' => function ($q) {
                $q->accountingActive();
            }], 'pending_amount')
            ->get();
        $products = Product::where('is_active', true)->get();
        $schemes = \App\Models\Scheme::active()->with('schemeSlabs')->get();
        $salesmen = Salesman::where('is_active', true)->orderBy('name')->get();
        $creditLimitPolicy = $this->creditLimitPolicy();
        
        return view('invoices.edit', compact('invoice', 'customers', 'products', 'schemes', 'creditLimitPolicy', 'salesmen'));
    }

    public function update(UpdateInvoiceRequest $request, $id)
    {
        $invoiceQuery = Invoice::query();
        $user = $request->user();
        $roleName = strtolower((string) ($user?->role?->name ?? ''));
        $forceSalesmanId = null;
        $forceSalesmanName = null;
        if ($roleName === 'salesman') {
            $salesmanId = $user?->salesman_id;
            if ($salesmanId) {
                $invoiceQuery->where('salesman_id', $salesmanId);
                $forceSalesmanId = $salesmanId;
                $forceSalesmanName = Salesman::query()->where('id', $salesmanId)->value('name');
            } else {
                $invoiceQuery->whereRaw('1=0');
            }
        }
        $invoice = $invoiceQuery->findOrFail($id);
        
        $validated = $request->validated();

        $warning = null;

        try {
            DB::transaction(function () use ($invoice, $validated, &$warning, $forceSalesmanId, $forceSalesmanName) {
                $customer = Customer::findOrFail($validated['customer_id']);
                if ($forceSalesmanId) {
                    $salesmanId = $forceSalesmanId;
                    $salesman = $forceSalesmanName ?: ($invoice->salesman ?? '');
                } else {
                    $salesman = $validated['salesman'] ?? ($invoice->salesman ?? Setting::get('default_salesman', ''));
                    $salesmanId = null;
                    if (is_string($salesman) && trim($salesman) !== '') {
                        $salesmanId = Salesman::query()
                            ->whereRaw('LOWER(name) = ?', [strtolower(trim($salesman))])
                            ->value('id');
                    }
                }
            
            $invoiceItemsData = [];
            $processedItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::with(['schemes.schemeSlabs'])->findOrFail($item['product_id']);
                
                $inputForCalc = [
                    'base_price' => $product->base_price,
                    'qty_ct' => $item['qty_ct'] ?? 0,
                    'qty_un' => $item['qty_un'] ?? 0,
                    'pack_size' => $product->pack_size,
                    'discount_pct' => $item['discount_pct'] ?? 0,
                    'gst_rate' => $product->gst_rate,
                    'is_free' => $item['is_free'] ?? false,
                    'mrp' => $product->mrp,
                ];

                $calcResult = GSTCalculator::calcItem($inputForCalc, $customer->tax_type, []);
                $processedItems[] = $calcResult;

                $invoiceItemsData[] = [
                    'product_id' => $product->id,
                    'is_free' => $inputForCalc['is_free'],
                    'product_description' => $product->name . ' ' . $product->volume,
                    'hsn_code' => $product->hsn_code,
                    'mrp' => $product->mrp,
                    'qty_ct' => $inputForCalc['qty_ct'],
                    'qty_un' => $inputForCalc['qty_un'],
                    'total_units' => $calcResult['total_units'],
                    'discount_pct' => $inputForCalc['discount_pct'],
                    'base_price' => $product->base_price,
                    'taxable' => $calcResult['taxable'],
                    'cgst_rate' => $calcResult['cgst_rate'],
                    'cgst' => $calcResult['cgst'],
                    'sgst_rate' => $calcResult['sgst_rate'],
                    'sgst' => $calcResult['sgst'],
                    'cess_rate' => 0,
                    'cess' => 0,
                    'line_total' => $calcResult['line_total'],
                ];
            }

                $totals = GSTCalculator::calcInvoiceTotals($processedItems);
                $amountInWords = AmountToWords::convert($totals['grand_total']);
                $paymentFields = $this->paymentFields($validated, (float) $totals['grand_total'], (float) $invoice->paid_amount);

                $limitSummary = $this->creditLimitSummary($customer, (float) $paymentFields['pending_amount'], $invoice);
                if ($limitSummary['exceeded']) {
                    $msg = "Credit limit exceeded for {$customer->name}. Outstanding: {$limitSummary['outstanding']}, New Pending: {$limitSummary['new_pending']}, Projected: {$limitSummary['projected']}, Limit: {$limitSummary['limit']}.";
                    if ($this->creditLimitPolicy() === 'block') {
                        throw new \RuntimeException($msg);
                    }
                    $warning = $msg;
                }

                $invoice->update(array_merge([
                'invoice_date' => $validated['invoice_date'],
                'customer_id' => $customer->id,
                'payment_mode' => $paymentFields['payment_mode'],
                'payment_type' => $paymentFields['payment_type'],
                'due_date' => $paymentFields['due_date'],
                'payment_status' => $paymentFields['payment_status'],
                'salesman' => $salesman,
                    'salesman_id' => $salesmanId,
                'po_no' => $validated['po_no'] ?? null,
                'tax_type' => $customer->tax_type,
                'amount_in_words' => $amountInWords,
                'total_amount' => $paymentFields['total_amount'],
                'paid_amount' => $paymentFields['paid_amount'],
                'pending_amount' => $paymentFields['pending_amount'],
                ], $totals));

            // Clear old items and insert new ones
            $invoice->invoiceItems()->delete();
            $invoice->invoiceItems()->createMany($invoiceItemsData);

            StockTransaction::where('reference_type', 'invoice')->where('reference_id', $invoice->id)->delete();
            $stockTx = [];
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $units = ((float) ($item['qty_ct'] ?? 0) * (int) $product->pack_size) + (float) ($item['qty_un'] ?? 0);
                if ($units <= 0) {
                    continue;
                }
                $stockTx[] = [
                    'product_id' => $product->id,
                    'type' => 'sale',
                    'quantity_in_units' => (int) round($units),
                    'notes' => 'Invoice ' . $invoice->invoice_no,
                    'transaction_date' => $invoice->invoice_date,
                    'reference_type' => 'invoice',
                    'reference_id' => $invoice->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($stockTx)) {
                StockTransaction::insert($stockTx);
            }
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        
        $redirect = redirect()->route('app.invoices.index')->with('success', 'Invoice updated successfully.');
        if ($warning) {
            $redirect = $redirect->with('warning', $warning);
        }
        return $redirect;
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->update(['is_deleted' => true, 'deleted_at' => now()]);
        StockTransaction::where('reference_type', 'invoice')->where('reference_id', $invoice->id)->delete();
        
        return redirect()->route('app.invoices.index')->with('success', 'Invoice deleted (soft).');
    }

    public function deleted()
    {
        $invoices = Invoice::where('is_deleted', true)->with('customer')->paginate(20);
        return view('invoices.deleted', compact('invoices'));
    }

    public function restore($id)
    {
        $invoice = Invoice::where('is_deleted', true)->findOrFail($id);
        $invoice->update(['is_deleted' => false, 'deleted_at' => null]);

        StockTransaction::where('reference_type', 'invoice')->where('reference_id', $invoice->id)->delete();
        $stockTx = [];
        foreach ($invoice->invoiceItems as $item) {
            $product = Product::find($item->product_id);
            if (!$product) {
                continue;
            }
            $units = (int) ($item->total_units ?? 0);
            if ($units <= 0) {
                continue;
            }
            $stockTx[] = [
                'product_id' => $product->id,
                'type' => 'sale',
                'quantity_in_units' => $units,
                'notes' => 'Invoice ' . $invoice->invoice_no,
                'transaction_date' => $invoice->invoice_date,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if (!empty($stockTx)) {
            StockTransaction::insert($stockTx);
        }
        
        return redirect()->route('app.invoices.deleted')->with('success', 'Invoice restored.');
    }

    public function forceDelete($id)
    {
        $invoice = Invoice::where('is_deleted', true)->findOrFail($id);
        StockTransaction::where('reference_type', 'invoice')->where('reference_id', $invoice->id)->delete();
        $invoice->invoiceItems()->delete();
        $invoice->delete();
        
        return redirect()->route('app.invoices.deleted')->with('success', 'Invoice permanently deleted.');
    }

    public function print(Request $request, $id)
    {
        $invoiceQuery = Invoice::with(['customer', 'invoiceItems.product']);
        $user = $request->user();
        $roleName = strtolower((string) ($user?->role?->name ?? ''));
        if ($roleName === 'salesman') {
            $salesmanId = $user?->salesman_id;
            if ($salesmanId) {
                $invoiceQuery->where('salesman_id', $salesmanId);
            } else {
                $invoiceQuery->whereRaw('1=0');
            }
        }
        $invoice = $invoiceQuery->findOrFail($id);
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $isSalesmanPanel = $request->routeIs('salesman.*');
        
        if ($request->has('pdf')) {
            $pdf = Pdf::loadView('invoices.print', compact('invoice', 'settings'))
                      ->setPaper('a4', 'landscape');
            $pdf->setOption('defaultFont', 'Helvetica');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->setOption('defaultMediaType', 'print');
            $pdf->setOption('dpi', 96);
            $safeInvoiceNo = str_replace(['/', '\\'], '-', $invoice->invoice_no);
            return $pdf->download("Invoice-{$safeInvoiceNo}.pdf");
        }

        if ($isSalesmanPanel && $roleName === 'salesman') {
            return view('invoices.print_thermal', compact('invoice', 'settings'));
        }

        return view('invoices.print', compact('invoice', 'settings')); 
    }

    public function detailsJson(Request $request, $id)
    {
        $invoiceQuery = Invoice::with(['customer', 'invoiceItems.product']);
        $user = $request->user();
        $roleName = strtolower((string) ($user?->role?->name ?? ''));
        if ($roleName === 'salesman') {
            $salesmanId = $user?->salesman_id;
            if ($salesmanId) {
                $invoiceQuery->where('salesman_id', $salesmanId);
            } else {
                $invoiceQuery->whereRaw('1=0');
            }
        }

        $invoice = $invoiceQuery->findOrFail($id);

        $status = (string) ($invoice->payment_status ?? '');
        if ($status === '') {
            $paid = (float) ($invoice->paid_amount ?? 0);
            $pending = (float) ($invoice->pending_amount ?? 0);
            if ($paid <= 0) {
                $status = 'Unpaid';
            } elseif ($pending <= 0) {
                $status = 'Paid';
            } else {
                $status = 'Partial';
            }
        }

        $taxType = (string) ($invoice->tax_type ?? '');
        $sgstLabel = $taxType === 'IGST'
            ? 'IGST'
            : ($taxType === 'CGST_UTGST' ? 'UTGST' : 'SGST');

        $customer = $invoice->customer;

        $items = $invoice->invoiceItems
            ->values()
            ->map(function ($it) use ($taxType) {
                $isFree = (bool) ($it->is_free ?? false);
                $pack = (int) ($it->product?->pack_size ?? 1);
                if ($pack <= 0) {
                    $pack = 1;
                }

                $taxable = $isFree ? 0.0 : (float) ($it->taxable ?? 0);
                $cgst = $isFree ? 0.0 : (float) ($it->cgst ?? 0);
                $sgst = $isFree ? 0.0 : (float) ($it->sgst ?? 0);
                $igst = 0.0;
                if ($taxType === 'IGST') {
                    $igst = $sgst;
                    $sgst = 0.0;
                }

                $lineTotal = $isFree ? 0.0 : (float) ($it->line_total ?? 0);

                return [
                    'is_free' => $isFree,
                    'product_code' => (string) ($it->product?->product_code ?? ''),
                    'product_description' => (string) ($it->product_description ?? ''),
                    'hsn_code' => (string) ($it->hsn_code ?? ''),
                    'pack' => $pack,
                    'qty_ct' => (float) ($it->qty_ct ?? 0),
                    'qty_un' => (float) ($it->qty_un ?? 0),
                    'total_units' => (float) ($it->total_units ?? 0),
                    'mrp' => (float) ($it->mrp ?? 0),
                    'taxable' => $taxable,
                    'cgst' => $cgst,
                    'sgst' => $sgst,
                    'igst' => $igst,
                    'cess' => $isFree ? 0.0 : (float) ($it->cess ?? 0),
                    'line_total' => $lineTotal,
                ];
            })
            ->all();

        $cgst = (float) ($invoice->total_cgst ?? 0);
        $sgst = (float) ($invoice->total_sgst ?? 0);
        $igst = 0.0;
        if ($taxType === 'IGST') {
            $igst = $sgst;
            $sgst = 0.0;
        }

        return response()->json([
            'invoice' => [
                'id' => (int) $invoice->id,
                'invoice_no' => (string) ($invoice->invoice_no ?? ''),
                'invoice_date' => $invoice->invoice_date ? $invoice->invoice_date->format('d-m-Y') : '',
                'tax_type' => $taxType,
                'sgst_label' => $sgstLabel,
                'payment_status' => $status,
                'payment_type' => (string) ($invoice->payment_type ?? ''),
                'payment_mode' => (string) ($invoice->payment_mode ?? ''),
                'due_date' => $invoice->due_date ? $invoice->due_date->format('d-m-Y') : '',
                'taxable_amount' => (float) ($invoice->taxable_amount ?? 0),
                'total_cgst' => $cgst,
                'total_sgst' => $sgst,
                'total_igst' => $igst,
                'total_cess' => (float) ($invoice->total_cess ?? 0),
                'grand_total' => (float) ($invoice->grand_total ?? 0),
                'paid_amount' => (float) ($invoice->paid_amount ?? 0),
                'pending_amount' => (float) ($invoice->pending_amount ?? 0),
            ],
            'customer' => [
                'id' => (int) ($customer?->id ?? 0),
                'name' => (string) ($customer?->name ?? ''),
                'business_name' => (string) ($customer?->business_name ?? ''),
                'gstin' => (string) ($customer?->gstin ?? ''),
                'mobile' => (string) ($customer?->mobile ?? ''),
                'email' => (string) ($customer?->email ?? ''),
                'address' => (string) ($customer?->address ?? ''),
                'city' => (string) ($customer?->city ?? ''),
                'state' => (string) ($customer?->state ?? ''),
                'state_code' => (string) ($customer?->state_code ?? ''),
                'pos_code' => (string) ($customer?->pos_code ?? ''),
                'pin_code' => (string) ($customer?->pin_code ?? ''),
            ],
            'items' => $items,
        ]);
    }

    public function nextNo()
    {
        return response()->json([
            'next_no' => InvoiceNumberGenerator::next()
        ]);
    }
}
