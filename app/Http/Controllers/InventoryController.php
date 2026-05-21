<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    private function normalizeLimit(mixed $value): int
    {
        $n = (int) $value;
        if ($n <= 0) {
            return 50;
        }
        return min(200, $n);
    }

    private function normalizeOffset(mixed $value): int
    {
        $n = (int) $value;
        return max(0, $n);
    }

    private function applyInventorySearch($query, string $q)
    {
        $term = trim($q);
        if ($term === '') {
            return $query;
        }
        return $query->where(function ($sq) use ($term) {
            $sq->where('products.name', 'like', '%' . $term . '%')
                ->orWhere('products.product_code', 'like', '%' . $term . '%')
                ->orWhere('products.hsn_code', 'like', '%' . $term . '%');
        });
    }

    private function inventoryStockQuery(?string $q = null)
    {
        $stockExpression = 'products.opening_stock_units + COALESCE(SUM(CASE
            WHEN stock_transactions.type = "purchase" THEN stock_transactions.quantity_in_units
            WHEN stock_transactions.type = "sale" THEN -stock_transactions.quantity_in_units
            WHEN stock_transactions.type = "adjustment" THEN stock_transactions.quantity_in_units
            ELSE 0 END), 0)';

        $query = DB::table('products')
            ->leftJoin('stock_transactions', 'products.id', '=', 'stock_transactions.product_id')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->groupBy('products.id', 'products.name', 'products.product_code', 'products.pack_size', 'products.opening_stock_units')
            ->select('products.id', 'products.name', 'products.product_code', 'products.pack_size')
            ->selectRaw("($stockExpression) as current_units")
            ->orderBy('products.name')
            ->orderBy('products.id');

        return $this->applyInventorySearch($query, (string) ($q ?? ''));
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = 50;
        $offset = 0;

        $rows = $this->inventoryStockQuery($q)
            ->offset($offset)
            ->limit($limit)
            ->get();

        $initialItems = $rows->map(function ($r) {
            return [
                'id' => (int) $r->id,
                'name' => (string) ($r->name ?? ''),
                'product_code' => (string) ($r->product_code ?? ''),
                'pack_size' => (int) ($r->pack_size ?? 1),
                'current_units' => (int) ($r->current_units ?? 0),
            ];
        })->values();

        $total = Product::query()
            ->where('is_active', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sq) use ($q) {
                    $sq->where('name', 'like', '%' . $q . '%')
                        ->orWhere('product_code', 'like', '%' . $q . '%')
                        ->orWhere('hsn_code', 'like', '%' . $q . '%');
                });
            })
            ->count();

        return view('inventory.index', [
            'initial_items' => $initialItems,
            'initial_total' => (int) $total,
            'initial_q' => $q,
            'initial_limit' => $limit,
        ]);
    }

    public function products(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = $this->normalizeLimit($request->query('limit', 50));
        $offset = $this->normalizeOffset($request->query('offset', 0));

        $rows = $this->inventoryStockQuery($q)
            ->offset($offset)
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $slice = $hasMore ? $rows->slice(0, $limit)->values() : $rows->values();

        $items = $slice->map(function ($r) {
            return [
                'id' => (int) $r->id,
                'name' => (string) ($r->name ?? ''),
                'product_code' => (string) ($r->product_code ?? ''),
                'pack_size' => (int) ($r->pack_size ?? 1),
                'current_units' => (int) ($r->current_units ?? 0),
            ];
        })->values();

        return response()->json([
            'items' => $items,
            'offset' => $offset,
            'limit' => $limit,
            'next_offset' => $offset + $items->count(),
            'has_more' => $hasMore,
        ]);
    }

    public function adjust(Request $request, Product $product)
    {
        $validated = $request->validate([
            'new_ct' => 'required|integer|min:0',
            'new_un' => 'required|integer|min:0',
        ]);

        $packSize = (int) ($product->pack_size ?? 1);
        if ($packSize <= 0) {
            $packSize = 1;
        }

        $newCt = (int) $validated['new_ct'];
        $newUn = (int) $validated['new_un'];
        $newUnits = ($newCt * $packSize) + $newUn;
        if ($newUnits < 0) {
            $newUnits = 0;
        }

        $row = $this->inventoryStockQuery(null)
            ->where('products.id', $product->id)
            ->first();

        $currentUnits = (int) ($row?->current_units ?? 0);
        $delta = $newUnits - $currentUnits;

        if ($delta !== 0) {
            StockTransaction::create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity_in_units' => $delta,
                'notes' => 'Stock adjusted',
                'transaction_date' => now(),
                'reference_type' => 'inventory_adjustment',
                'reference_id' => null,
            ]);
        }

        $ct = (int) floor($newUnits / $packSize);
        $un = (int) ($newUnits % $packSize);

        return response()->json([
            'ok' => true,
            'message' => 'Stock updated successfully',
            'item' => [
                'id' => (int) $product->id,
                'current_units' => $newUnits,
                'current_stock' => $ct . ' CT & ' . $un . ' UN',
            ],
        ]);
    }

    public function show(Product $product)
    {
        return view('inventory.show', compact('product'));
    }

    public function storeTransaction(Request $request, Product $product)
    {
        $request->validate([
            'type' => 'required|string|in:purchase,sale,adjustment',
            'quantity_ct' => 'nullable|integer|min:0',
            'quantity_un' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'transaction_date' => 'required|date',
        ]);

        $quantity_ct = $request->input('quantity_ct', 0);
        $quantity_un = $request->input('quantity_un', 0);
        $pack_size = $product->pack_size > 0 ? $product->pack_size : 1;

        $total_units = ($quantity_ct * $pack_size) + $quantity_un;

        if ($total_units <= 0) {
            return back()->with('error', 'Quantity must be greater than zero.');
        }

        $product->stockTransactions()->create([
            'type' => $request->type,
            'quantity_in_units' => $total_units,
            'notes' => $request->notes,
            'transaction_date' => $request->transaction_date,
        ]);

        return back()->with('success', 'Transaction recorded successfully.');
    }
}
