<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnsurePermission
{
    private function moduleKeyForRouteName(?string $name): ?string
    {
        if (!is_string($name) || $name === '') {
            return null;
        }

        if ($name === 'app.dashboard' || Str::startsWith($name, 'app.api.dashboard')) {
            return 'dashboard';
        }

        $prefixMap = [
            'app.products' => 'products',
            'app.api.products' => 'products',

            'app.customers' => 'customers',
            'app.api.customers' => 'customers',

            'app.suppliers' => 'suppliers',
            'app.api.suppliers' => 'suppliers',

            'app.inventory' => 'inventory',
            'app.api.inventory' => 'inventory',
            'app.opening-stock' => 'inventory',

            'app.purchases' => 'purchases',

            'app.invoices' => 'sales_invoices',
            'app.api.invoice' => 'sales_invoices',

            'app.receivables' => 'receivables',

            'app.accounts' => 'accounts',
            'app.api.accounts' => 'accounts',
            'app.payments' => 'accounts',
            'app.api.payments' => 'accounts',

            'app.reports' => 'reports',
            'app.stock-report' => 'reports',

            'app.masters' => 'masters',
            'app.categories' => 'masters',
            'app.schemes' => 'masters',
            'app.product-groups' => 'masters',
            'app.group-schemes' => 'masters',

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

    private function actionForRoute(Request $request): string
    {
        $name = (string) ($request->route()?->getName() ?? '');
        $last = $name !== '' ? Str::afterLast($name, '.') : '';

        $deleteActions = ['destroy', 'forceDelete', 'restore', 'deleted'];
        $editActions = ['edit', 'update', 'toggle', 'transaction'];
        $addActions = ['create', 'store', 'upload', 'commit', 'cancel', 'save', 'add'];
        $viewActions = [
            'index', 'show', 'print',
            'template', 'imports', 'preview',
            'search', 'data', 'products',
            'pending_invoices', 'customer_ledger',
            'forProduct', 'nextNo', 'invoices', 'aging',
        ];

        if (in_array($last, $deleteActions, true)) {
            return 'delete';
        }
        if (in_array($last, $editActions, true)) {
            return 'edit';
        }
        if (in_array($last, $addActions, true)) {
            return $last === 'create' ? 'add' : ($last === 'store' ? 'add' : ($last === 'upload' || $last === 'commit' || $last === 'cancel' ? 'add' : ($last === 'add' ? 'add' : 'edit')));
        }
        if (in_array($last, $viewActions, true)) {
            return 'view';
        }

        if ($request->isMethod('get') || $request->isMethod('head')) {
            return 'view';
        }
        if ($request->isMethod('delete')) {
            return 'delete';
        }

        return 'edit';
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $roleName = strtolower((string) ($user->role?->name ?? ''));
        if ($roleName === 'salesman') {
            $path = ltrim((string) $request->path(), '/');
            if (Str::startsWith($path, 'app')) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Access Denied'], 403);
                }
                return redirect('/salesman/invoices');
            }
        }

        $isAdmin = $roleName === '' || $roleName === 'admin';
        if ($isAdmin) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $moduleKey = $this->moduleKeyForRouteName($routeName);
        if (!$moduleKey) {
            return $next($request);
        }

        $action = $this->actionForRoute($request);
        $requiredCode = $moduleKey . '.' . $action;

        $allowedCodeSet = $user->role?->permissions()
            ->wherePivot('allowed', true)
            ->pluck('permissions.code')
            ->flip()
            ->all() ?? [];

        if (config('app.env') !== 'production' && ($request->expectsJson() || Str::startsWith((string) $routeName, 'app.api.'))) {
            Log::info('[RBAC] check', [
                'user_id' => $user->id,
                'role' => $user->role?->name,
                'role_id' => $user->role_id,
                'salesman_id' => $user->salesman_id,
                'route' => $routeName,
                'module' => $moduleKey,
                'action' => $action,
                'required' => $requiredCode,
            ]);
        }

        if (isset($allowedCodeSet[$requiredCode])) {
            return $next($request);
        }

        $message = 'You do not have permission to perform this action';
        if ($request->expectsJson() || Str::startsWith((string) $routeName, 'app.api.')) {
            return response()->json(['message' => $message], 403);
        }

        return response()->view('errors.403', ['message' => $message], 403);
    }
}
