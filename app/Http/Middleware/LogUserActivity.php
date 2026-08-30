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
        $response = $next($request); // ⚠️ මේ line එක අනිවාර්යයෙන් ඕන - request එක process කරන්නේ මෙතනින්

        if (Auth::check()) {
            $userId = Auth::id();
            $companyId = Auth::user()->company_id;
            $routeName = $request->route()?->getName() ?? $request->path();
            $ip = $request->ip();
            $userAgent = $request->userAgent();
            $url = $request->fullUrl();
            $method = $request->method();

            dispatch(function () use ($userId, $companyId, $routeName, $ip, $userAgent, $url, $method) {
                UserActivity::create([
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'action' => $routeName,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'details' => [
                        'method' => $method,
                        'url' => $url,
                    ],
                ]);
            })->afterResponse();
        }

        return $response;
    }
}