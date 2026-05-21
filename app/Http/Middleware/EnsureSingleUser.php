<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // If a user already exists and someone is trying to access the register route
        if ($request->is('register')) {
            try {
                if (Schema::hasTable('users') && User::count() >= 1) {
                    return redirect('/login')->with('error', 'Registration is closed. Only one admin user is allowed.');
                }
            } catch (\Throwable) {
                return $next($request);
            }
        }

        return $next($request);
    }
}
