<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RateLimiter
{
    public function handle(Request $request, Closure $next, $limit = 10, $minutes = 1)
    {
        $key = 'rate_limit:' . $request->ip() . ':' . $request->route()->getName();

        $current = Cache::get($key, 0);

        if ($current >= $limit) {
            return response()->json([
                'error' => 'Too many requests. Please try again later.'
            ], 429);
        }

        Cache::put($key, $current + 1, now()->addMinutes($minutes));

        return $next($request);
    }
}