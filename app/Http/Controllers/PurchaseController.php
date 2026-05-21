<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\StockTransaction;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    private function defaultSupplier(): ?Supplier
    {
        return Supplier::query()
            ->whereRaw('LOWER(name) = ?', ['varun beverages'])
            ->first();
    }

    public function index(Request $request)
    {
        $query = Purchase::with(['purchaseItems.product', 'supplier_ref'])->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('bill_no', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%")
                  ->orWhereHas('supplier_ref', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('bill_date', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('bill_date', '<=', $endDate);
        }

        $purchases = $query->paginate(20)->withQueryString();
        $products = Product::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();
        return view('purchases.index', compact('purchases', 'products', 'suppliers'));
    }

    public function show($id)
    {
        $purchase = Purchase::with(['purchaseItems.product', 'supplier_ref'])->findOrFail($id);

        return view('purchases.show', compact('purchase'));
    }

    public function store(StorePurchaseRequest $request)
    {
        $validated = $request->validated();

        if (empty($validated['supplier_id'])) {
            $default = $this->defaultSupplier();
            if ($default) {
                $validated['supplier_id'] = $default->id;
                $validated['supplier'] = $default->name;
            }
        }
        
        DB::transaction(function () use ($validated) {
            $totalAmount = 0;
            $purchaseItemsData = [];
            $stockTx = [];

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $qtyCt = (float) ($item['qty_ct'] ?? 0);
                    $rate = (float) ($item['purchase_rate'] ?? 0);
                    $totalUnits = $qtyCt * (int) $product->pack_size;
                    $itemTotal = round($qtyCt * $rate, 6);
                    $totalAmount += $itemTotal;

                    $purchaseItemsData[] = [
                        'product_id' => $item['product_id'],
                        'qty_ct' => $qtyCt,
                        'total_units' => $totalUnits,
                        'purchase_rate' => $rate,
                        'item_total' => $itemTotal,
                    ];
                }
            }

            $purchase = Purchase::create([
                'bill_no' => $validated['bill_no'],
                'bill_date' => $validated['bill_date'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'supplier' => $validated['supplier'] ?? null,
                'vehicle_number' => $validated['vehicle_number'] ?? null,
                'total_amount' => round($totalAmount, 6),
            ]);

            $purchase->purchaseItems()->createMany($purchaseItemsData);

            foreach ($purchaseItemsData as $pi) {
                $product = Product::find($pi['product_id']);
                if (!$product) {
                    continue;
                }
                $units = (int) round($pi['total_units'] ?? 0);
                if ($units <= 0) {
                    continue;
                }
                $stockTx[] = [
                    'product_id' => $product->id,
                    'type' => 'purchase',
                    'quantity_in_units' => $units,
                    'notes' => 'Purchase ' . $purchase->bill_no,
                    'transaction_date' => $purchase->bill_date,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($stockTx)) {
                StockTransaction::insert($stockTx);
            }
        });
        
        return redirect()->route('app.purchases.index')->with('success', 'Purchase recorded successfully.');
    }

    public function edit($id)
    {
        $purchase = Purchase::with(['purchaseItems.product', 'supplier_ref'])->findOrFail($id);
        return response()->json($purchase);
    }

    public function update(UpdatePurchaseRequest $request, $id)
    {
        $purchase = Purchase::findOrFail($id);
        $validated = $request->validated();

        if (empty($validated['supplier_id'])) {
            $default = $this->defaultSupplier();
            if ($default) {
                $validated['supplier_id'] = $default->id;
                $validated['supplier'] = $default->name;
            }
        }

        DB::transaction(function () use ($purchase, $validated) {
            $totalAmount = 0;
            $purchaseItemsData = [];
            $stockTx = [];

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $qtyCt = (float) ($item['qty_ct'] ?? 0);
                    $rate = (float) ($item['purchase_rate'] ?? 0);
                    $totalUnits = $qtyCt * (int) $product->pack_size;
                    $itemTotal = round($qtyCt * $rate, 6);
                    $totalAmount += $itemTotal;

                    $purchaseItemsData[] = [
                        'product_id' => $item['product_id'],
                        'qty_ct' => $qtyCt,
                        'total_units' => $totalUnits,
                        'purchase_rate' => $rate,
                        'item_total' => $itemTotal,
                    ];
                }
            }

            $purchase->update([
                'bill_no' => $validated['bill_no'],
                'bill_date' => $validated['bill_date'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'supplier' => $validated['supplier'] ?? null,
                'vehicle_number' => $validated['vehicle_number'] ?? null,
                'total_amount' => round($totalAmount, 6),
            ]);

            $purchase->purchaseItems()->delete();
            $purchase->purchaseItems()->createMany($purchaseItemsData);

            StockTransaction::where('reference_type', 'purchase')->where('reference_id', $purchase->id)->delete();
            foreach ($purchaseItemsData as $pi) {
                $product = Product::find($pi['product_id']);
                if (!$product) {
                    continue;
                }
                $units = (int) round($pi['total_units'] ?? 0);
                if ($units <= 0) {
                    continue;
                }
                $stockTx[] = [
                    'product_id' => $product->id,
                    'type' => 'purchase',
                    'quantity_in_units' => $units,
                    'notes' => 'Purchase ' . $purchase->bill_no,
                    'transaction_date' => $purchase->bill_date,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($stockTx)) {
                StockTransaction::insert($stockTx);
            }
        });

        return redirect()->route('app.purchases.index')->with('success', 'Purchase updated successfully.');
    }

    public function destroy($id)
    {
        $purchase = Purchase::findOrFail($id);
        $purchase->delete(); // Soft delete
        StockTransaction::where('reference_type', 'purchase')->where('reference_id', $purchase->id)->delete();
        
        return redirect()->route('app.purchases.index')->with('success', 'Purchase deleted successfully.');
    }
}
