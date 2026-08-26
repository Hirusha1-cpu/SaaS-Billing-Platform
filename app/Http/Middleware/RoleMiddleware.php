<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if user has any of the required roles
        foreach ($roles as $role) {
            // Intelephense now knows $user points to your User Model and has the hasRole() method!
            if ($user->hasRole($role) || $user->role === $role) {
                return $next($request);
            }
        }

        return response()->json(['error' => 'Forbidden - Insufficient permissions'], 403);
    }
}
