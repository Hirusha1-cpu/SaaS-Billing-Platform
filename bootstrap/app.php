<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global Middleware
        $middleware->use([
            \Illuminate\Foundation\Http\Middleware\TrustHosts::class,
            \Illuminate\Foundation\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        // Web Middleware Group
        $middleware->group('web', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // API Middleware Group
        $middleware->group('api', [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\MultiTenant::class,
        ]);

        // Middleware Aliases
        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

            // Custom Middlewares
            'multitenant' => \App\Http\Middleware\MultiTenant::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'rate.limiter' => \App\Http\Middleware\RateLimiter::class,
            'verify.webhook' => \App\Http\Middleware\VerifyWebhook::class,
            'invoice.locked' => \App\Http\Middleware\InvoiceLocked::class,
            'check.subscription' => \App\Http\Middleware\CheckSubscription::class,
            'log.activity' => \App\Http\Middleware\LogUserActivity::class,
        ]);

        // Excluded routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'api/webhook/stripe',
            'api/webhook/paypal',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => get_class($e),
                    'code' => $e->getCode(),
                ], 500);
            }
        });
    })
    ->withCommands([
        \App\Console\Commands\ProcessBilling::class,
        \App\Console\Commands\CheckOverdue::class,
        \App\Console\Commands\SendReminders::class,
        \App\Console\Commands\GenerateReports::class,
        \App\Console\Commands\CleanAuditLogs::class,
        \App\Console\Commands\SyncSubscriptions::class,
    ])
    ->create();