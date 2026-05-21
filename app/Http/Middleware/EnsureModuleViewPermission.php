<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EnsureModuleViewPermission
{
    private function moduleKeyForRouteName(?string $name): ?string
    {
        if (!is_string($name) || $name === '') {
            return null;
        }

        if ($name === 'app.dashboard') {
            return 'dashboard';
        }

        $prefixMap = [
            'app.products' => 'products',
            'app.customers' => 'customers',
            'app.suppliers' => 'suppliers',
            'app.inventory' => 'inventory',
            'app.opening-stock' => 'inventory',
            'app.purchases' => 'purchases',
            'app.invoices' => 'sales_invoices',
            'app.receivables' => 'receivables',
            'app.accounts' => 'accounts',
            'app.payments' => 'accounts',
            'app.reports' => 'reports',
            'app.stock-report' => 'reports',
            'app.masters' => 'masters',
            'app.categories' => 'masters',
            'app.schemes' => 'masters',
            'app.settings' => 'settings',
            'app.roles' => 'users',
            'app.users' => 'users',
        ];

        foreach ($prefixMap as $prefix => $moduleKey) {
            if (Str::startsWith($name, $prefix)) {
                return $moduleKey;
            }
        }

        return null;
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $roleName = strtolower((string) ($user->role?->name ?? ''));
        if ($roleName === '' || $roleName === 'admin') {
            return $next($request);
        }

        $moduleKey = $this->moduleKeyForRouteName($request->route()?->getName());
        if (!$moduleKey) {
            return $next($request);
        }

        $allowedCodes = $user->role?->permissions()
            ->wherePivot('allowed', true)
            ->pluck('permissions.code')
            ->flip()
            ->all();

        $required = $moduleKey . '.view';
        $hasAccess = isset($allowedCodes[$required]);

        if ($hasAccess) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Access Denied'], 403);
        }

        return response()->view('errors.403', ['module' => $moduleKey], 403);
    }
}

