<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $roles = Role::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->get();

        $modules = [
            'Dashboard',
            'Customers',
            'Products',
            'Suppliers',
            'Inventory',
            'Purchases',
            'Sales (Invoices)',
            'Receivables',
            'Accounts',
            'Reports',
            'Masters',
            'Settings',
            'Users',
        ];
        $actions = ['View', 'Add', 'Edit', 'Delete'];

        $permissions = Permission::query()
            ->whereIn('module', $modules)
            ->get()
            ->groupBy('module');

        $permissionsMatrix = [];
        foreach ($modules as $module) {
            $byAction = ($permissions->get($module) ?? collect())->keyBy('action');
            $row = [
                'module' => $module,
                'actions' => [],
            ];
            foreach ($actions as $action) {
                $p = $byAction->get($action);
                $row['actions'][] = [
                    'action' => $action,
                    'code' => $p?->code,
                ];
            }
            $permissionsMatrix[] = $row;
        }

        return view('roles.index', compact('roles', 'search', 'permissionsMatrix', 'actions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'permission_codes' => 'nullable|array',
            'permission_codes.*' => 'string|exists:permissions,code',
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        $permissionCodes = $validated['permission_codes'] ?? [];
        unset($validated['permission_codes']);

        $role = Role::create($validated);

        if (! empty($permissionCodes)) {
            $permissionIds = Permission::query()
                ->whereIn('code', $permissionCodes)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
        }

        return redirect()->route('app.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(string $id)
    {
        $role = Role::findOrFail($id);

        $permissionCodes = $role->permissions()
            ->wherePivot('allowed', true)
            ->pluck('permissions.code')
            ->values();

        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
            'is_active' => (bool) $role->is_active,
            'permission_codes' => $permissionCodes,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'permission_codes' => 'nullable|array',
            'permission_codes.*' => 'string|exists:permissions,code',
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        $permissionCodes = $validated['permission_codes'] ?? [];
        unset($validated['permission_codes']);

        $role->update($validated);

        $permissionIds = Permission::query()
            ->whereIn('code', $permissionCodes)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);

        return redirect()->route('app.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        $name = strtolower(trim((string) $role->name));
        $nameCompact = str_replace([' ', '_', '-'], '', $name);

        $protected = ['admin', 'superadmin', 'salesman'];
        if (in_array($nameCompact, $protected, true)) {
            return back()->with('error', 'This role cannot be deleted.');
        }

        $usersCount = User::query()->where('role_id', $role->id)->count();
        if ($usersCount > 0) {
            return back()->with('error', 'This role is assigned to users. Reassign users before deleting.');
        }

        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }
}
