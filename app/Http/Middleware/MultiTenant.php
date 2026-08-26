<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MultiTenant
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $companyId = $user->company_id;

            // Set company ID in session
            session(['company_id' => $companyId]);
            
            // Set company ID in request
            $request->merge(['company_id' => $companyId]);
        }

        return $next($request);
    }
}