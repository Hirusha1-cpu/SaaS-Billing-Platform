<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $query = Payment::with(['invoice', 'customer']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from_date) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);

        return PaymentResource::collection($payments);
    }

    public function store(PaymentRequest $request)
    {
        DB::beginTransaction();
        try {
            $payment = $this->paymentService->createPayment($request->validated());

            DB::commit();

            return new PaymentResource($payment->load(['invoice', 'customer']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Payment $payment)
    {
        Gate::authorize('view', $payment);

        return new PaymentResource($payment->load(['invoice', 'customer']));
    }

    public function refund(Payment $payment, Request $request)
    {
        Gate::authorize('refund', $payment);

        $validator = validator($request->all(), [
            'amount' => 'nullable|numeric|min:0|max:' . $payment->amount,
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $refund = $this->paymentService->refundPayment($payment, $request->amount, $request->reason);

            DB::commit();

            return response()->json([
                'message' => 'Refund processed successfully',
                'refund' => $refund,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getStats(Request $request)
    {
        $stats = [
            'total_payments' => Payment::where('status', 'completed')->count(),
            'total_amount' => Payment::where('status', 'completed')->sum('amount'),
            'today' => Payment::whereDate('payment_date', today())
                ->where('status', 'completed')
                ->sum('amount'),
            'this_week' => Payment::whereBetween('payment_date', [now()->startOfWeek(), now()->endOfWeek()])
                ->where('status', 'completed')
                ->sum('amount'),
            'this_month' => Payment::whereMonth('payment_date', now()->month)
                ->where('status', 'completed')
                ->sum('amount'),
            'refunded' => Payment::where('status', 'refunded')->sum('amount'),
            'failed' => Payment::where('status', 'failed')->count(),
        ];

        return response()->json($stats);
    }

    public function processStripeWebhook(Request $request)
    {
        Log::info('started');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        // 🔍 DEBUG LOGS
        Log::info('🔔 Webhook received', [
            'payload_length' => strlen($payload),
            'sig_header_exists' => !empty($sigHeader),
            'webhook_secret_exists' => !empty($webhookSecret),
            'webhook_secret' => substr($webhookSecret, 0, 20) . '...',
        ]);

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );

            Log::info('✅ Webhook verified successfully', [
                'type' => $event->type,
                'id' => $event->id,
            ]);

            // Process the event
            $this->paymentService->handleStripeWebhook($event);

            return response()->json(['status' => 'success'], 200);
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('❌ Invalid webhook payload', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            Log::error('❌ Invalid webhook signature', [
                'error' => $e->getMessage(),
                'webhook_secret' => $webhookSecret,
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }
    }
    public function createStripeSession(Request $request)
    {
        // Get invoice_id from request
        $invoiceId = $request->input('invoice_id');

        // Debug log
        Log::info('Create Stripe Session called', [
            'invoice_id' => $invoiceId,
            'all_input' => $request->all(),
        ]);

        if (!$invoiceId) {
            return response()->json(['error' => 'Invoice ID is required'], 422);
        }

        // Load invoice with customer
        $invoice = Invoice::with('customer')->find($invoiceId);

        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        // Check if invoice can be paid
        if ($invoice->status === 'paid') {
            return response()->json(['error' => 'Invoice is already paid'], 422);
        }

        // Check if invoice belongs to user's company
        if (Auth::check() && Auth::user()->company_id !== $invoice->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $checkout = $this->paymentService->createStripeCheckoutSession($invoice);

            return response()->json([
                'checkout_url' => $checkout->url,
                'session_id' => $checkout->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe session creation failed', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoiceId,
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    // public function createStripeSession(Invoice $invoice, Request $request)
    // {
    //     // 1. Check if user is authenticated
    //     if (!Auth::check()) {
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }

    //     // 2. Check if invoice belongs to user's company
    //     // if (Auth::user()->company_id !== $invoice->company_id) {
    //     //     return response()->json(['error' => 'Unauthorized'], 403);
    //     // }

    //     // 3. Check if invoice can be paid
    //     if ($invoice->status === 'paid') {
    //         return response()->json(['error' => 'Invoice is already paid'], 422);
    //     }

    //     try {
    //         $checkout = $this->paymentService->createStripeCheckoutSession($invoice);

    //         return response()->json([
    //             'checkout_url' => $checkout->url,
    //             'session_id' => $checkout->id,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }
    public function confirmStripePayment(Request $request)
    {
        $validator = validator($request->all(), [
            'payment_intent_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $payment = $this->paymentService->confirmStripePayment($request->payment_intent_id);

            return response()->json([
                'message' => 'Payment confirmed successfully',
                'payment' => new PaymentResource($payment),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function paymentSuccess(Request $request)
    {
        $sessionId = $request->query('session_id');

        // Check if payment exists
        $payment = Payment::where('transaction_id', $sessionId)->first();

        return view('payment.success', [
            'session_id' => $sessionId,
            'payment' => $payment,
        ]);
    }

    public function paymentCancel(Request $request)
    {
        return view('payment.cancel');
    }
}
