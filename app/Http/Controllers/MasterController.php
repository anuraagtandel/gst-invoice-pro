<?php

namespace App\Http\Controllers;

use App\Models\HsnCode;
use App\Models\UnitType;
use App\Models\PackType;
use App\Models\Brand;
use App\Models\Volume;
use App\Models\Area;
use App\Models\PackSize;
use App\Models\Category;
use App\Models\Salesman;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    private function upper(?string $value): ?string
    {
        $v = trim((string) $value);
        return $v === '' ? '' : strtoupper($v);
    }

    public function index()
    {
        $hsnCodes = HsnCode::orderBy('code')->get();
        $unitTypes = UnitType::orderBy('code')->get();
        $packTypes = PackType::orderBy('code')->get();

        $brands = Brand::orderBy('name')->get();
        $volumes = Volume::orderBy('name')->get();

        $areas = Area::orderBy('name')->get();

        $packSizes = PackSize::orderBy('units_per_ct')->get();

        $categories = Category::orderBy('name')->get();

        $salesmen = Salesman::with('area')->orderBy('name')->get();

        return view('masters.index', compact('hsnCodes', 'unitTypes', 'packTypes', 'brands', 'volumes', 'areas', 'packSizes', 'categories', 'salesmen'));
    }

    // HSN Codes
    public function storeHsn(Request $request)
    {
        $request->merge([
            'code' => $this->upper($request->input('code')),
            'description' => $this->upper($request->input('description')),
        ]);

        $validated = $request->validate(
            [
                'code' => 'required|digits:8|unique:hsn_codes,code',
                'description' => 'nullable|string|max:255',
            ],
            [
                'code.required' => 'HSN code is required.',
                'code.digits' => 'HSN code must be exactly 8 digits.',
                'code.unique' => 'This HSN code already exists.',
            ]
        );
        HsnCode::create($validated);
        return back()->with('success', 'HSN Code added successfully.');
    }

    public function destroyHsn(HsnCode $hsnCode)
    {
        $hsnCode->delete();
        return back()->with('success', 'HSN Code deleted successfully.');
    }

    // Unit Types
    public function storeUnitType(Request $request)
    {
        $request->merge([
            'code' => $this->upper($request->input('code')),
        ]);

        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:unit_types',
        ]);
        UnitType::create($validated);
        return back()->with('success', 'Unit Type added successfully.');
    }

    public function destroyUnitType(UnitType $unitType)
    {
        $unitType->delete();
        return back()->with('success', 'Unit Type deleted successfully.');
    }

    // Pack Types
    public function storePackType(Request $request)
    {
        $request->merge([
            'code' => $this->upper($request->input('code')),
        ]);

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:pack_types',
        ]);
        PackType::create($validated);
        return back()->with('success', 'Pack Type added successfully.');
    }

    public function destroyPackType(PackType $packType)
    {
        $packType->delete();
        return back()->with('success', 'Pack Type deleted successfully.');
    }

    // Brands
    public function storeBrand(Request $request)
    {
        $request->merge([
            'name' => $this->upper($request->input('name')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:brands,name',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        Brand::create($validated);
        return back()->with('success', 'Brand added successfully.');
    }

    public function destroyBrand(Brand $brand)
    {
        $brand->delete();
        return back()->with('success', 'Brand deleted successfully.');
    }

    // Volumes
    public function storeVolume(Request $request)
    {
        $request->merge([
            'name' => $this->upper($request->input('name')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:volumes,name',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        Volume::create($validated);
        return back()->with('success', 'Volume added successfully.');
    }

    public function destroyVolume(Volume $volume)
    {
        $volume->delete();
        return back()->with('success', 'Volume deleted successfully.');
    }

    // Areas
    public function storeArea(Request $request)
    {
        $request->merge([
            'name' => $this->upper($request->input('name')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:areas,name',
        ]);
        Area::create($validated);
        return back()->with('success', 'Area added successfully.');
    }

    public function destroyArea(Area $area)
    {
        $area->delete();
        return back()->with('success', 'Area deleted successfully.');
    }

    // Pack Sizes
    public function storePackSize(Request $request)
    {
        $validated = $request->validate([
            'units_per_ct' => 'required|integer|min:1|unique:pack_sizes,units_per_ct',
        ]);
        $validated['is_active'] = true;
        PackSize::create($validated);
        return back()->with('success', 'Pack Size added successfully.');
    }

    public function destroyPackSize(PackSize $packSize)
    {
        $packSize->delete();
        return back()->with('success', 'Pack Size deleted successfully.');
    }

    // Categories
    public function storeCategory(Request $request)
    {
        $request->merge([
            'name' => $this->upper($request->input('name')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);
        Category::create(['name' => $validated['name'], 'is_active' => true]);
        return back()->with('success', 'Category added successfully.');
    }

    public function destroyCategory(Category $category)
    {
        $category->delete();
        return back()->with('success', 'Category deleted successfully.');
    }
}
