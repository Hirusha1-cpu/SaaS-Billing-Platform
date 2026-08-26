<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('Stripe-Signature');
        
        if (!$signature) {
            Log::warning('Webhook signature missing', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            return response()->json(['error' => 'Signature missing'], 401);
        }

        try {
            // Verify signature using Stripe
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $signature,
                config('services.stripe.webhook_secret')
            );

            $request->merge(['stripe_event' => $event]);
            
            Log::info('Webhook verified successfully', [
                'event_type' => $event->type,
                'event_id' => $event->id,
            ]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Webhook verification failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }
    }
}