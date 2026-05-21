<?php

namespace App\Http\Controllers;

use App\Models\Salesman;
use Illuminate\Http\Request;

class SalesmanController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'mobile' => ['nullable', 'string', 'regex:/^\d{10}$/'],
            'email' => 'nullable|email|max:150',
            'area_id' => 'nullable|exists:areas,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Salesman::create($validated);

        return back()->with('success', 'Salesman added successfully.');
    }

    public function edit($id)
    {
        $salesman = Salesman::findOrFail($id);
        return response()->json($salesman);
    }

    public function update(Request $request, $id)
    {
        $salesman = Salesman::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'mobile' => ['nullable', 'string', 'regex:/^\d{10}$/'],
            'email' => 'nullable|email|max:150',
            'area_id' => 'nullable|exists:areas,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $salesman->update($validated);

        return back()->with('success', 'Salesman updated successfully.');
    }

    public function destroy($id)
    {
        $salesman = Salesman::findOrFail($id);
        $salesman->delete();

        return back()->with('success', 'Salesman deleted successfully.');
    }

    public function toggle($id)
    {
        $salesman = Salesman::findOrFail($id);
        $salesman->is_active = !$salesman->is_active;
        $salesman->save();

        return back()->with('success', 'Salesman status updated.');
    }
}

