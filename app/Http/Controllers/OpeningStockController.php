<?php

namespace App\Http\Controllers;

use App\Models\OpeningStock;
use App\Models\Product;
use Illuminate\Http\Request;

class OpeningStockController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('name')->get();

        return view('opening_stock.index', compact('products'));
    }

    public function addStock(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'cartons' => 'nullable|numeric|min:0',
            'bottles' => 'nullable|numeric|min:0',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $cartons = (float) ($validated['cartons'] ?? 0);
        $bottles = (float) ($validated['bottles'] ?? 0);
        $packSize = (int) ($product->pack_size > 0 ? $product->pack_size : 1);

        $openingUnits = (int) round(($cartons * $packSize) + $bottles);

        $product->opening_stock_units = $openingUnits;
        $product->save();

        OpeningStock::updateOrCreate(
            ['product_id' => $product->id],
            [
                'opening_ct' => (int) floor($openingUnits / $packSize),
                'opening_un' => (int) ($openingUnits % $packSize),
            ]
        );

        return redirect()->route('app.opening-stock.index')->with('success', 'Opening stock updated.');
    }

    public function save(Request $request)
    {
        return redirect()->route('app.opening-stock.index');
    }
}
