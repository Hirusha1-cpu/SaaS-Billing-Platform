<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserActivity;
use Illuminate\Support\Facades\Auth;

class LogUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            try {
                UserActivity::create([
                    'user_id' => Auth::id(),
                    'company_id' => Auth::user()->company_id,
                    'action' => $request->route()->getName() ?? $request->path(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'details' => [
                        'method' => $request->method(),
                        'url' => $request->fullUrl(),
                    ],
                ]);
            } catch (\Exception $e) {
                // Don't break the request if logging fails
            }
        }

        return $next($request);
    }
}