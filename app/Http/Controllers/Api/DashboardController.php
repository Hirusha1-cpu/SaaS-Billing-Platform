<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics (Optimized with Caching)
     */
    public function stats(Request $request)
    {
        $companyId = Auth::user()->company_id;
        
        // Cache key for 5 minutes
        $cacheKey = 'dashboard_stats_' . $companyId;
        
        $stats = Cache::remember($cacheKey, 300, function () use ($companyId) {
            // Use one query to get invoice counts
            $invoiceCounts = Invoice::where('company_id', $companyId)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            // Use one query for revenue
            $revenue = Invoice::where('company_id', $companyId)
                ->where('status', 'paid')
                ->select(DB::raw('COALESCE(SUM(total), 0) as total'))
                ->first();

            // Use one query for pending amount
            $pendingAmount = Invoice::where('company_id', $companyId)
                ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
                ->select(DB::raw('COALESCE(SUM(balance_due), 0) as total'))
                ->first();

            // Customer counts
            $customerCounts = Customer::where('company_id', $companyId)
                ->select(DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active'))
                ->first();

            // Subscription counts
            $subscriptionCounts = Subscription::where('company_id', $companyId)
                ->where('status', 'active')
                ->count();

            // Monthly revenue (last 6 months) - optimized
            $monthlyRevenue = Invoice::where('company_id', $companyId)
                ->where('status', 'paid')
                ->where('paid_at', '>=', now()->subMonths(6))
                ->select(
                    DB::raw('EXTRACT(YEAR FROM paid_at) as year'),
                    DB::raw('EXTRACT(MONTH FROM paid_at) as month'),
                    DB::raw('SUM(total) as total')
                )
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            // Recent invoices (limit 5)
            $recentInvoices = Invoice::where('company_id', $companyId)
                ->with(['customer', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Recent payments (limit 5)
            $recentPayments = Payment::where('company_id', $companyId)
                ->with(['invoice', 'customer'])
                ->where('status', 'completed')
                ->orderBy('payment_date', 'desc')
                ->limit(5)
                ->get();

            // Chart data (last 7 days) - optimized with single query
            $chartData = $this->getChartDataOptimized($companyId);

            return [
                'total_invoices' => array_sum($invoiceCounts),
                'draft_invoices' => $invoiceCounts['draft'] ?? 0,
                'sent_invoices' => $invoiceCounts['sent'] ?? 0,
                'paid_invoices' => $invoiceCounts['paid'] ?? 0,
                'overdue_invoices' => $invoiceCounts['overdue'] ?? 0,
                'partially_paid_invoices' => $invoiceCounts['partially_paid'] ?? 0,
                'total_revenue' => (float) ($revenue->total ?? 0),
                'pending_amount' => (float) ($pendingAmount->total ?? 0),
                'total_customers' => $customerCounts->total ?? 0,
                'active_customers' => $customerCounts->active ?? 0,
                'active_subscriptions' => $subscriptionCounts,
                'monthly_revenue' => $monthlyRevenue,
                'recent_invoices' => $recentInvoices,
                'recent_payments' => $recentPayments,
                'chart_data' => $chartData,
            ];
        });

        return response()->json($stats);
    }

    /**
     * Optimized chart data - uses single query
     */
    private function getChartDataOptimized($companyId)
    {
        $days = 7;
        $startDate = now()->subDays($days - 1)->startOfDay();

        // Get daily invoice counts
        $dailyInvoices = Invoice::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Get daily revenue
        $dailyRevenue = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->where('paid_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(paid_at) as date'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateKey = $date->format('Y-m-d');

            $data[] = [
                'date' => $dateKey,
                'label' => $date->format('D'),
                'invoices' => $dailyInvoices[$dateKey] ?? 0,
                'revenue' => (float) ($dailyRevenue[$dateKey] ?? 0),
            ];
        }

        return $data;
    }

    /**
     * Get recent invoices (cached)
     */
    public function recentInvoices(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $limit = $request->get('limit', 10);

        $cacheKey = 'recent_invoices_' . $companyId . '_' . $limit;

        $invoices = Cache::remember($cacheKey, 300, function () use ($companyId, $limit) {
            return Invoice::where('company_id', $companyId)
                ->with(['customer', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });

        return response()->json($invoices);
    }

    /**
     * Get recent activity (cached)
     */
    public function activity(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $limit = $request->get('limit', 10);

        $cacheKey = 'recent_activity_' . $companyId . '_' . $limit;

        $activity = Cache::remember($cacheKey, 300, function () use ($companyId, $limit) {
            // Get recent invoices and payments
            $invoices = Invoice::where('company_id', $companyId)
                ->with(['customer', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'invoice',
                        'id' => $item->id,
                        'description' => 'Invoice #' . $item->invoice_number . ' created',
                        'status' => $item->status,
                        'created_at' => $item->created_at,
                        'data' => $item,
                    ];
                });

            $payments = Payment::where('company_id', $companyId)
                ->with(['invoice', 'customer'])
                ->where('status', 'completed')
                ->orderBy('payment_date', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'payment',
                        'id' => $item->id,
                        'description' => 'Payment received for invoice #' . ($item->invoice->invoice_number ?? 'N/A'),
                        'amount' => $item->amount,
                        'created_at' => $item->payment_date,
                        'data' => $item,
                    ];
                });

            // Merge and sort
            return $invoices->concat($payments)
                ->sortByDesc('created_at')
                ->take($limit)
                ->values();
        });

        return response()->json($activity);
    }

    /**
     * Get summary stats (cached)
     */
    public function summary(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $cacheKey = 'dashboard_summary_' . $companyId;

        $stats = Cache::remember($cacheKey, 300, function () use ($companyId) {
            return [
                'total_invoices' => Invoice::where('company_id', $companyId)->count(),
                'pending_invoices' => Invoice::where('company_id', $companyId)
                    ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
                    ->count(),
                'total_revenue' => (float) Invoice::where('company_id', $companyId)
                    ->where('status', 'paid')
                    ->sum('total'),
                'total_customers' => Customer::where('company_id', $companyId)->count(),
                'active_subscriptions' => Subscription::where('company_id', $companyId)
                    ->where('status', 'active')
                    ->count(),
            ];
        });

        return response()->json($stats);
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache(Request $request)
    {
        $companyId = Auth::user()->company_id;
        
        Cache::forget('dashboard_stats_' . $companyId);
        Cache::forget('dashboard_summary_' . $companyId);
        
        return response()->json(['message' => 'Dashboard cache cleared']);
    }
}