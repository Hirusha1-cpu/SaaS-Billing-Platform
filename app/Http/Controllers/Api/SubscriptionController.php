<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Customer;
use App\Http\Requests\SubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function index(Request $request)
    {
        $query = Subscription::with(['customer']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->billing_cycle) {
            $query->where('billing_cycle', $request->billing_cycle);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(15);

        return SubscriptionResource::collection($subscriptions);
    }

    public function store(SubscriptionRequest $request)
    {
        DB::beginTransaction();
        try {
            $subscription = $this->subscriptionService->createSubscription($request->validated());

            DB::commit();

            return new SubscriptionResource($subscription->load(['customer']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Subscription $subscription)
    {
        Gate::authorize('view', $subscription);

        return new SubscriptionResource($subscription->load(['customer', 'invoices']));
    }

    public function update(SubscriptionRequest $request, Subscription $subscription)
    {
        Gate::authorize('update', $subscription);

        DB::beginTransaction();
        try {
            $subscription = $this->subscriptionService->updateSubscription($subscription, $request->validated());

            DB::commit();

            return new SubscriptionResource($subscription->load(['customer']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Subscription $subscription)
    {
        Gate::authorize('delete', $subscription);

        DB::beginTransaction();
        try {
            $this->subscriptionService->cancelSubscription($subscription);

            DB::commit();

            return response()->json(['message' => 'Subscription cancelled successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function pause(Subscription $subscription, Request $request)
    {
        Gate::authorize('update', $subscription);

        DB::beginTransaction();
        try {
            $subscription = $this->subscriptionService->pauseSubscription($subscription);

            DB::commit();

            return response()->json([
                'message' => 'Subscription paused successfully',
                'subscription' => new SubscriptionResource($subscription),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function resume(Subscription $subscription)
    {
        Gate::authorize('update', $subscription);

        DB::beginTransaction();
        try {
            $subscription = $this->subscriptionService->resumeSubscription($subscription);

            DB::commit();

            return response()->json([
                'message' => 'Subscription resumed successfully',
                'subscription' => new SubscriptionResource($subscription),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function processBilling(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = $this->subscriptionService->processDailyBilling();

            DB::commit();

            return response()->json([
                'message' => 'Billing processed successfully',
                'processed' => $result['processed'],
                'failed' => $result['failed'] ?? 0,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getStats(Request $request)
    {
        $stats = [
            'active' => Subscription::where('status', 'active')->count(),
            'trialing' => Subscription::where('status', 'trialing')->count(),
            'cancelled' => Subscription::where('status', 'cancelled')->count(),
            'paused' => Subscription::where('status', 'paused')->count(),
            'expired' => Subscription::where('status', 'expired')->count(),
            'monthly' => Subscription::where('billing_cycle', 'monthly')
                ->where('status', 'active')->count(),
            'yearly' => Subscription::where('billing_cycle', 'yearly')
                ->where('status', 'active')->count(),
            'total_revenue' => Subscription::where('status', 'active')
                ->whereHas('invoices', function($q) {
                    $q->where('status', 'paid');
                })->sum('amount'),
        ];

        return response()->json($stats);
    }
}