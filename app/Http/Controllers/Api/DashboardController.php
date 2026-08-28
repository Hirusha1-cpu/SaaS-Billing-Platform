<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        $companyId = Auth::user()->company_id;
        
        $cacheKey = 'dashboard_stats_' . $companyId;
        
        $stats = Cache::remember($cacheKey, 300, function () use ($companyId) {
            $invoiceCounts = Invoice::where('company_id', $companyId)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $revenue = Invoice::where('company_id', $companyId)
                ->where('status', 'paid')
                ->select(DB::raw('COALESCE(SUM(total), 0) as total'))
                ->first();

            $pendingAmount = Invoice::where('company_id', $companyId)
                ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
                ->select(DB::raw('COALESCE(SUM(balance_due), 0) as total'))
                ->first();

            $customerCounts = Customer::where('company_id', $companyId)
                ->select(DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active'))
                ->first();

            $subscriptionCounts = Subscription::where('company_id', $companyId)
                ->where('status', 'active')
                ->count();

            return [
                'total_invoices' => array_sum($invoiceCounts),
                'draft_invoices' => $invoiceCounts['draft'] ?? 0,
                'sent_invoices' => $invoiceCounts['sent'] ?? 0,
                'paid_invoices' => $invoiceCounts['paid'] ?? 0,
                'overdue_invoices' => $invoiceCounts['overdue'] ?? 0,
                'partially_paid_invoices' => $invoiceCounts['partially_paid'] ?? 0,
                'pending_invoices' => ($invoiceCounts['sent'] ?? 0) + ($invoiceCounts['partially_paid'] ?? 0) + ($invoiceCounts['overdue'] ?? 0),
                'total_revenue' => (float) ($revenue->total ?? 0),
                'pending_amount' => (float) ($pendingAmount->total ?? 0),
                'total_customers' => $customerCounts->total ?? 0,
                'active_customers' => $customerCounts->active ?? 0,
                'active_subscriptions' => $subscriptionCounts,
            ];
        });

        return response()->json($stats);
    }

    /**
     * Get recent invoices (converted to array)
     */
    public function recentInvoices(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $limit = $request->get('limit', 5);

        $invoices = Invoice::where('company_id', $companyId)
            ->with(['customer', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'total' => (float) $invoice->total,
                    'status' => $invoice->status,
                    'balance_due' => (float) $invoice->balance_due,
                    'currency' => $invoice->currency,
                    'created_at' => $invoice->created_at,
                    'customer' => $invoice->customer ? [
                        'id' => $invoice->customer->id,
                        'name' => $invoice->customer->name,
                        'email' => $invoice->customer->email,
                    ] : null,
                ];
            });

        return response()->json($invoices);
    }

    /**
     * Get recent activity (converted to array)
     */
    public function activity(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $limit = $request->get('limit', 10);

        // Get recent invoices
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
                    'amount' => (float) $item->total,
                    'data' => [
                        'invoice_number' => $item->invoice_number,
                        'customer' => $item->customer ? $item->customer->name : null,
                    ],
                ];
            });

        // Get recent payments
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
                    'status' => 'completed',
                    'created_at' => $item->payment_date,
                    'amount' => (float) $item->amount,
                    'data' => [
                        'amount' => $item->amount,
                        'currency' => $item->currency,
                    ],
                ];
            });

        // Merge and sort
        $activity = $invoices->concat($payments)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->toArray();

        return response()->json($activity);
    }

    /**
     * Get summary stats (converted to array)
     */
    public function summary(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $stats = [
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

        return response()->json($stats);
    }
}