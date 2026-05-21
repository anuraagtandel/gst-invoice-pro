<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductGroup;
use Illuminate\Http\Request;

class ProductGroupController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $groups = ProductGroup::query()
            ->with(['products' => function ($q) {
                $q->where('is_active', true)->orderBy('name');
            }])
            ->when($search !== '', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                $label = trim($p->name . ' ' . ($p->volume ?? ''));
                $pack = (int) ($p->pack_size ?? 0);
                if ($pack > 0) {
                    $label .= ' (Pack: ' . $pack . ')';
                }
                return [
                    'id' => $p->id,
                    'label' => $label,
                ];
            })
            ->values();

        return view('product-groups.index', compact('groups', 'products', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:product_groups,name',
            'description' => 'nullable|string|max:1000',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $productIds = $validated['product_ids'] ?? [];
        unset($validated['product_ids']);

        $group = ProductGroup::create($validated);
        $group->products()->sync($productIds);

        return redirect()->route('app.product-groups.index')->with('success', 'Product group created successfully.');
    }

    public function edit(string $id)
    {
        $group = ProductGroup::query()->with('products:id')->findOrFail($id);

        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
            'description' => $group->description,
            'product_ids' => $group->products->pluck('id')->values(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $group = ProductGroup::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:product_groups,name,' . $group->id,
            'description' => 'nullable|string|max:1000',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $productIds = $validated['product_ids'] ?? [];
        unset($validated['product_ids']);

        $group->update($validated);
        $group->products()->sync($productIds);

        return redirect()->route('app.product-groups.index')->with('success', 'Product group updated successfully.');
    }
}

