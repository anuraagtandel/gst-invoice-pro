<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSchemeRequest;
use App\Http\Requests\UpdateSchemeRequest;
use App\Models\Product;
use App\Models\Scheme;
use Illuminate\Support\Facades\DB;

class SchemeController extends Controller
{
    public function index()
    {
        $schemes = Scheme::with(['product', 'schemeSlabs'])->paginate(20);
        $products = Product::where('is_active', true)->get();

        return view('schemes.index', compact('schemes', 'products'));
    }

    public function store(StoreSchemeRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $scheme = Scheme::create([
                'name' => $validated['name'],
                'product_id' => $validated['product_id'],
                'valid_from' => $validated['valid_from'] ?? null,
                'valid_to' => $validated['valid_to'] ?? null,
            ]);

            if (isset($validated['slabs']) && is_array($validated['slabs'])) {
                $scheme->schemeSlabs()->createMany($validated['slabs']);
            }
        });

        return redirect()->route('app.schemes.index')->with('success', 'Scheme created successfully.');
    }

    public function show($id)
    {
        $scheme = Scheme::with('schemeSlabs')->findOrFail($id);

        return response()->json($scheme);
    }

    public function update(UpdateSchemeRequest $request, $id)
    {
        $scheme = Scheme::findOrFail($id);
        $validated = $request->validated();

        DB::transaction(function () use ($scheme, $validated) {
            $scheme->update([
                'name' => $validated['name'],
                'product_id' => $validated['product_id'],
                'valid_from' => $validated['valid_from'] ?? null,
                'valid_to' => $validated['valid_to'] ?? null,
            ]);

            if (isset($validated['slabs']) && is_array($validated['slabs'])) {
                $scheme->schemeSlabs()->delete();
                $scheme->schemeSlabs()->createMany($validated['slabs']);
            }
        });

        return redirect()->route('app.schemes.index')->with('success', 'Scheme updated successfully.');
    }

    public function destroy($id)
    {
        $scheme = Scheme::findOrFail($id);
        $scheme->delete();

        return redirect()->route('app.schemes.index')->with('success', 'Scheme deleted successfully.');
    }

    public function toggle($id)
    {
        $scheme = Scheme::findOrFail($id);
        $scheme->is_active = ! $scheme->is_active;
        $scheme->save();

        return back()->with('success', 'Status updated.');
    }

    public function forProduct($productId)
    {
        $schemes = Scheme::with('schemeSlabs')
            ->where('product_id', $productId)
            ->active()
            ->get();

        return response()->json($schemes);
    }
}
