<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Salesman;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->with('role')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $salesmen = Salesman::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('users.index', compact('users', 'roles', 'salesmen', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'salesman_id' => ['nullable', 'integer', 'exists:salesmen,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => (int) $validated['role_id'],
            'salesman_id' => isset($validated['salesman_id']) && $validated['salesman_id'] !== '' ? (int) $validated['salesman_id'] : null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('app.users.index')->with('success', 'User created successfully.');
    }

    public function edit(string $id)
    {
        $user = User::query()->with('role')->findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'salesman_id' => $user->salesman_id,
            'is_active' => (bool) ($user->is_active ?? true),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class . ',email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'salesman_id' => ['nullable', 'integer', 'exists:salesmen,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => (int) $validated['role_id'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
        if (array_key_exists('salesman_id', $validated)) {
            $data['salesman_id'] = isset($validated['salesman_id']) && $validated['salesman_id'] !== '' ? (int) $validated['salesman_id'] : null;
        }

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('app.users.index')->with('success', 'User updated successfully.');
    }
}
