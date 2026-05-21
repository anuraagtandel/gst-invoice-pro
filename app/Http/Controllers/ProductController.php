<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $productsQuery = Product::query()->orderBy('name');
        if ($q !== '') {
            $productsQuery->where(function ($qq) use ($q) {
                $qq->where('name', 'like', '%' . $q . '%')
                    ->orWhere('product_code', 'like', '%' . $q . '%')
                    ->orWhere('brand', 'like', '%' . $q . '%')
                    ->orWhere('category', 'like', '%' . $q . '%')
                    ->orWhere('hsn_code', 'like', '%' . $q . '%');
            });
        }

        $products = $productsQuery->paginate(15)->appends(['q' => $q]);
        $categories = \App\Models\Category::orderBy('name')->get();
        $hsnCodes = \App\Models\HsnCode::where('is_active', true)->orderBy('code')->get();
        $packTypes = \App\Models\PackType::where('is_active', true)->orderBy('code')->get();
        $brands = \App\Models\Brand::where('is_active', true)->orderBy('name')->get();
        $volumes = \App\Models\Volume::where('is_active', true)->orderBy('name')->get();
        $packSizes = \App\Models\PackSize::where('is_active', true)->orderBy('units_per_ct')->get();
        return view('products.index', compact('products', 'categories', 'hsnCodes', 'packTypes', 'brands', 'volumes', 'packSizes', 'q'));
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();
        $validated['unit_type'] = 'CT';
        
        if (isset($validated['base_price']) && isset($validated['gst_rate'])) {
            $validated['trade_price'] = round($validated['base_price'] * (1 + ($validated['gst_rate'] / 100)), 6);
        }

        $opening_stock_ct = $validated['opening_stock_ct'] ?? 0;
        $opening_stock_un = $validated['opening_stock_un'] ?? 0;
        $pack_size = $validated['pack_size'] ?? 1;

        $validated['opening_stock_units'] = ($opening_stock_ct * $pack_size) + $opening_stock_un;

        Product::create($validated);
        
        return redirect()->route('app.products.index')->with('success', 'Product created successfully.');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validated();
        $validated['unit_type'] = 'CT';
        
        if (isset($validated['base_price']) && isset($validated['gst_rate'])) {
            $validated['trade_price'] = round($validated['base_price'] * (1 + ($validated['gst_rate'] / 100)), 6);
        }

        $product->update($validated);
        
        return redirect()->route('app.products.index')->with('success', 'Product updated successfully.');
    }

    public function toggle($id)
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => !$product->is_active]);
        
        return redirect()->back()->with('success', 'Product status toggled.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        
        return redirect()->route('app.products.index')->with('success', 'Product deleted successfully.');
    }

    public function search(Request $request)
    {
        $q = $request->input('q');
        $products = Product::where('name', 'like', "%{$q}%")
            ->orWhere('hsn_code', 'like', "%{$q}%")
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'label' => trim("{$p->name} {$p->volume} {$p->pack_size}PK MRP:{$p->mrp}"),
                    'mrp' => $p->mrp,
                    'purchase_rate' => $p->purchase_rate,
                    'base_price' => $p->base_price,
                    'gst_rate' => $p->gst_rate,
                    'hsn_code' => $p->hsn_code,
                    'pack_size' => $p->pack_size,
                ];
            });
            
        return response()->json($products);
    }
}
