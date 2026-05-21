<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $suppliers = Supplier::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'like', '%' . $search . '%')
                        ->orWhere('gstin', 'like', '%' . $search . '%')
                        ->orWhere('pan', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['state'] = $validated['state'] ?? 'Daman';

        $supplier = Supplier::create($validated);

        return redirect()
            ->route('app.suppliers.index', ['search' => $supplier->name])
            ->with('success', 'Supplier created successfully.')
            ->with('highlight_id', $supplier->id);
    }

    public function edit(string $id)
    {
        $supplier = Supplier::findOrFail($id);

        return response()->json($supplier);
    }

    public function update(UpdateSupplierRequest $request, string $id)
    {
        $supplier = Supplier::findOrFail($id);
        $validated = $request->validated();
        $validated['is_active'] = (bool) ($validated['is_active'] ?? $supplier->is_active);
        $validated['state'] = $validated['state'] ?? $supplier->state ?? 'Daman';

        $supplier->update($validated);

        return redirect()
            ->route('app.suppliers.index', ['search' => $supplier->name])
            ->with('success', 'Supplier updated successfully.')
            ->with('highlight_id', $supplier->id);
    }

    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return redirect()->route('app.suppliers.index')->with('success', 'Supplier deleted successfully.');
    }

    public function toggle(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->is_active = ! $supplier->is_active;
        $supplier->save();

        return back()->with('success', 'Status updated.');
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('gstin', 'like', "%{$q}%")
                    ->orWhere('pan', 'like', "%{$q}%")
                    ->orWhere('mobile', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(25)
            ->get()
            ->map(function (Supplier $s) {
                return [
                    'id' => $s->id,
                    'label' => $s->name,
                    'gstin' => $s->gstin,
                    'mobile' => $s->mobile,
                ];
            });

        return response()->json($suppliers);
    }
}
