<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            $redirectRoute = match (true) {
                str_contains($request->getHost(), 'admin.') => 'admin.login',
                str_contains($request->getHost(), 'guru.') => 'guru.login',
                default => 'login',
            };

            return redirect()->route($redirectRoute);
        }

        $user = $request->user();
        $userRole = strtolower(trim((string) $user->getAttribute('role')));
        $allowedRoles = array_map(fn ($role) => strtolower(trim((string) $role)), $roles);

        if (! in_array($userRole, $allowedRoles, true)) {
            $target = match ($userRole) {
                'admin' => 'admin.dashboard',
                'guru' => 'guru.dashboard',
                default => 'siswa.dashboard',
            };

            return redirect()->route($target);
        }

        return $next($request);
    }
}
