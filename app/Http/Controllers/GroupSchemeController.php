<?php

namespace App\Http\Controllers;

use App\Models\GroupScheme;
use App\Models\Product;
use App\Models\ProductGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupSchemeController extends Controller
{
    private function validateSlabs(array $slabs): array
    {
        $normalized = [];
        foreach ($slabs as $row) {
            $min = isset($row['min_qty_ct']) ? (int) $row['min_qty_ct'] : null;
            $max = isset($row['max_qty_ct']) ? (int) $row['max_qty_ct'] : null;
            $free = isset($row['free_qty_units']) ? (int) $row['free_qty_units'] : null;

            if ($min === null || $max === null || $free === null) {
                continue;
            }

            $normalized[] = [
                'min_qty_ct' => $min,
                'max_qty_ct' => $max,
                'free_qty_units' => $free,
            ];
        }

        usort($normalized, function ($a, $b) {
            return $a['min_qty_ct'] <=> $b['min_qty_ct'];
        });

        $errors = [];
        $prevMax = null;
        foreach ($normalized as $i => $s) {
            if ($s['min_qty_ct'] <= 0) {
                $errors[] = "Slab row " . ($i + 1) . ": Min Quantity must be greater than 0.";
            }
            if ($s['max_qty_ct'] <= 0) {
                $errors[] = "Slab row " . ($i + 1) . ": Max Quantity must be greater than 0.";
            }
            if ($s['min_qty_ct'] > $s['max_qty_ct']) {
                $errors[] = "Slab row " . ($i + 1) . ": Min Quantity must be less than or equal to Max Quantity.";
            }
            if ($s['free_qty_units'] <= 0) {
                $errors[] = "Slab row " . ($i + 1) . ": Free Quantity must be greater than 0.";
            }
            if ($prevMax !== null && $s['min_qty_ct'] <= $prevMax) {
                $errors[] = "Slab row " . ($i + 1) . ": Slabs must not overlap.";
            }
            $prevMax = max($prevMax ?? 0, $s['max_qty_ct']);
        }

        if (count($normalized) === 0) {
            $errors[] = 'At least one slab is required.';
        }

        return [$normalized, $errors];
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $schemes = GroupScheme::query()
            ->with(['productGroup', 'freeProduct', 'slabs'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->get();

        $groups = ProductGroup::query()->orderBy('name')->get();

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

        return view('group-schemes.index', compact('schemes', 'groups', 'products', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'product_group_id' => 'required|integer|exists:product_groups,id',
            'free_product_id' => 'required|integer|exists:products,id',
            'is_active' => 'nullable|boolean',
            'slabs' => 'required|array',
            'slabs.*.min_qty_ct' => 'required|integer',
            'slabs.*.max_qty_ct' => 'required|integer',
            'slabs.*.free_qty_units' => 'required|integer',
        ]);

        [$slabs, $slabErrors] = $this->validateSlabs($validated['slabs']);
        if (!empty($slabErrors)) {
            return back()->withErrors($slabErrors)->withInput();
        }

        DB::transaction(function () use ($validated, $slabs) {
            $scheme = GroupScheme::create([
                'name' => $validated['name'],
                'product_group_id' => (int) $validated['product_group_id'],
                'free_product_id' => (int) $validated['free_product_id'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $scheme->slabs()->createMany($slabs);
        });

        return redirect()->route('app.group-schemes.index')->with('success', 'Scheme created successfully.');
    }

    public function edit(string $id)
    {
        $scheme = GroupScheme::query()->with('slabs')->findOrFail($id);

        return response()->json([
            'id' => $scheme->id,
            'name' => $scheme->name,
            'product_group_id' => $scheme->product_group_id,
            'free_product_id' => $scheme->free_product_id,
            'is_active' => (bool) $scheme->is_active,
            'slabs' => $scheme->slabs->map(fn ($s) => [
                'min_qty_ct' => (int) $s->min_qty_ct,
                'max_qty_ct' => (int) $s->max_qty_ct,
                'free_qty_units' => (int) $s->free_qty_units,
            ])->values(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $scheme = GroupScheme::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'product_group_id' => 'required|integer|exists:product_groups,id',
            'free_product_id' => 'required|integer|exists:products,id',
            'is_active' => 'nullable|boolean',
            'slabs' => 'required|array',
            'slabs.*.min_qty_ct' => 'required|integer',
            'slabs.*.max_qty_ct' => 'required|integer',
            'slabs.*.free_qty_units' => 'required|integer',
        ]);

        [$slabs, $slabErrors] = $this->validateSlabs($validated['slabs']);
        if (!empty($slabErrors)) {
            return back()->withErrors($slabErrors)->withInput();
        }

        DB::transaction(function () use ($scheme, $validated, $slabs) {
            $scheme->update([
                'name' => $validated['name'],
                'product_group_id' => (int) $validated['product_group_id'],
                'free_product_id' => (int) $validated['free_product_id'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $scheme->slabs()->delete();
            $scheme->slabs()->createMany($slabs);
        });

        return redirect()->route('app.group-schemes.index')->with('success', 'Scheme updated successfully.');
    }

    public function toggle(string $id)
    {
        $scheme = GroupScheme::findOrFail($id);
        $scheme->is_active = ! $scheme->is_active;
        $scheme->save();

        return back()->with('success', 'Status updated.');
    }
}

