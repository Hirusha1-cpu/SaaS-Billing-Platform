<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Checkout\Session;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createPayment(array $data)
    {
        return DB::transaction(function () use ($data) {
            $invoice = Invoice::find($data['invoice_id']);

            if (!$invoice) {
                throw new \Exception('Invoice not found');
            }

            if ($invoice->isPaid()) {
                throw new \Exception('Invoice is already paid');
            }

            // Create payment record
            $payment = Payment::create([
                'company_id' => Auth::user()->company_id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? $invoice->currency ?? 'LKR',
                'payment_method' => $data['payment_method'] ?? 'stripe',
                'status' => 'pending',
                'payment_date' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            Log::info('Payment record created', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
            ]);

            return $payment;
        });
    }
    public function createStripeCheckoutSession(Invoice $invoice)
    {
        $baseUrl = config('app.url');

        // Load customer relationship if not loaded
        if (!$invoice->relationLoaded('customer')) {
            $invoice->load('customer');
        }

        // Get customer email - safe check
        $customerEmail = $invoice->customer?->email ?? 'customer@example.com';
        $customerName = $invoice->customer?->name ?? 'Customer';

        // Log for debugging
        Log::info('Creating Stripe session', [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'customer_email' => $customerEmail,
            'customer_name' => $customerName,
        ]);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => strtolower($invoice->currency ?? 'lkr'),
                        'product_data' => [
                            'name' => 'Invoice #' . $invoice->invoice_number,
                            'description' => 'Payment for invoice ' . $invoice->invoice_number . ' - ' . $customerName,
                        ],
                        'unit_amount' => (int) ($invoice->balance_due * 100),
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $baseUrl . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $baseUrl . '/payment/cancel',
            'metadata' => [
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
            ],
            'customer_email' => $customerEmail,
        ]);

        Log::info('Stripe checkout session created', [
            'session_id' => $session->id,
            'invoice_id' => $invoice->id,
            'customer_email' => $customerEmail,
        ]);

        return $session;
    }
    // public function createStripeCheckoutSession(Invoice $invoice)
    // {
    //     $baseUrl = config('app.url');

    //     $session = Session::create([
    //         'payment_method_types' => ['card'],
    //         'line_items' => [
    //             [
    //                 'price_data' => [
    //                     'currency' => strtolower($invoice->currency ?? 'lkr'),
    //                     'product_data' => [
    //                         'name' => 'Invoice #' . $invoice->invoice_number,
    //                         'description' => 'Payment for invoice ' . $invoice->invoice_number,
    //                     ],
    //                     'unit_amount' => (int) ($invoice->balance_due * 100),
    //                 ],
    //                 'quantity' => 1,
    //             ],
    //         ],
    //         'mode' => 'payment',
    //         'success_url' => $baseUrl . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
    //         'cancel_url' => $baseUrl . '/payment/cancel',
    //         'metadata' => [
    //             'invoice_id' => $invoice->id,
    //             'user_id' => Auth::id(),
    //         ],
    //         'customer_email' => $invoice->customer->email,
    //     ]);

    //     Log::info('Stripe checkout session created', [
    //         'session_id' => $session->id,
    //         'invoice_id' => $invoice->id,
    //     ]);

    //     return $session;
    // }

    public function confirmStripePayment($paymentIntentId)
    {
        $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

        if ($paymentIntent->status === 'succeeded') {
            // Find payment record
            $payment = Payment::where('transaction_id', $paymentIntentId)->first();

            if (!$payment) {
                throw new \Exception('Payment record not found');
            }

            // Update payment status
            $payment->update([
                'status' => 'completed',
                'stripe_payment_intent_id' => $paymentIntentId,
                'stripe_charge_id' => $paymentIntent->charges->data[0]->id ?? null,
                'receipt_url' => $paymentIntent->charges->data[0]->receipt_url ?? null,
            ]);

            // Update invoice
            $invoice = $payment->invoice;
            $remaining = $invoice->balance_due - $payment->amount;

            $invoice->update([
                'status' => $remaining <= 0 ? 'paid' : 'partially_paid',
                'paid_amount' => $invoice->paid_amount + $payment->amount,
                'balance_due' => max(0, $remaining),
                'paid_at' => $remaining <= 0 ? now() : null,
            ]);

            Log::info('Payment confirmed successfully', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
            ]);

            return $payment;
        }

        throw new \Exception('Payment not completed');
    }

    public function handleStripeWebhook($event)
    {
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($event);
                break;

            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event);
                break;

            default:
                Log::info('Unhandled webhook event', ['type' => $event->type]);
        }
    }

    protected function handleCheckoutCompleted($event)
    {
        $session = $event->data->object;
        $invoiceId = $session->metadata->invoice_id ?? null;

        // Log for debugging
        Log::info('Webhook - Checkout completed received', [
            'invoice_id' => $invoiceId,
            'session_id' => $session->id,
            'payment_status' => $session->payment_status,
        ]);

        if ($invoiceId) {
            // Check if payment already exists
            $existingPayment = Payment::where('transaction_id', $session->id)->first();

            if (!$existingPayment) {
                // Find invoice
                $invoice = Invoice::find($invoiceId);

                if ($invoice) {
                    $amount = $session->amount_total / 100;
                    $currency = strtoupper($session->currency);

                    // Create payment
                    $payment = Payment::create([
                        'company_id' => $invoice->company_id,
                        'invoice_id' => $invoice->id,
                        'customer_id' => $invoice->customer_id,
                        'amount' => $amount,
                        'currency' => $currency,
                        'payment_method' => 'stripe',
                        'status' => 'completed',
                        'transaction_id' => $session->id,
                        'stripe_payment_intent_id' => $session->payment_intent,
                        'stripe_charge_id' => null,
                        'receipt_url' => null,
                        'payment_date' => now(),
                        'notes' => 'Payment for invoice #' . $invoice->invoice_number,
                    ]);

                    // Update invoice
                    $remaining = $invoice->balance_due - $amount;
                    $invoice->update([
                        'status' => $remaining <= 0 ? 'paid' : 'partially_paid',
                        'paid_amount' => $invoice->paid_amount + $amount,
                        'balance_due' => max(0, $remaining),
                        'paid_at' => $remaining <= 0 ? now() : null,
                    ]);

                    Log::info('Payment processed from webhook', [
                        'payment_id' => $payment->id,
                        'invoice_id' => $invoiceId,
                        'amount' => $amount,
                    ]);
                }
            }
        }
    }
    // protected function handleCheckoutCompleted($event)
    // {
    //     $session = $event->data->object;
    //     $invoiceId = $session->metadata->invoice_id ?? null;

    //     if ($invoiceId) {
    //         $payment = Payment::create([
    //             'company_id' => Auth::user()->company_id ?? null,
    //             'invoice_id' => $invoiceId,
    //             'amount' => $session->amount_total / 100,
    //             'currency' => strtoupper($session->currency),
    //             'payment_method' => 'stripe',
    //             'status' => 'pending',
    //             'transaction_id' => $session->id,
    //             'stripe_payment_intent_id' => $session->payment_intent,
    //             'payment_date' => now(),
    //         ]);

    //         Log::info('Payment recorded from webhook', [
    //             'payment_id' => $payment->id,
    //             'invoice_id' => $invoiceId,
    //         ]);
    //     }
    // }

    protected function handlePaymentSucceeded($event)
    {
        $paymentIntent = $event->data->object;

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($payment) {
            $payment->update([
                'status' => 'completed',
                'stripe_charge_id' => $paymentIntent->charges->data[0]->id ?? null,
                'receipt_url' => $paymentIntent->charges->data[0]->receipt_url ?? null,
            ]);

            // Update invoice
            $invoice = $payment->invoice;
            if ($invoice && !$invoice->isPaid()) {
                $remaining = $invoice->balance_due - $payment->amount;
                $invoice->update([
                    'status' => $remaining <= 0 ? 'paid' : 'partially_paid',
                    'paid_amount' => $invoice->paid_amount + $payment->amount,
                    'balance_due' => max(0, $remaining),
                    'paid_at' => $remaining <= 0 ? now() : null,
                ]);
            }

            Log::info('Payment succeeded from webhook', [
                'payment_id' => $payment->id,
                'payment_intent' => $paymentIntent->id,
            ]);
        }
    }

    protected function handlePaymentFailed($event)
    {
        $paymentIntent = $event->data->object;

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($payment) {
            $payment->update([
                'status' => 'failed',
            ]);

            Log::warning('Payment failed from webhook', [
                'payment_id' => $payment->id,
                'payment_intent' => $paymentIntent->id,
                'error' => $paymentIntent->last_payment_error ?? null,
            ]);
        }
    }

    public function refundPayment(Payment $payment, $amount = null, $reason = null)
    {
        if (!$payment->isCompleted()) {
            throw new \Exception('Cannot refund a payment that is not completed');
        }

        if ($payment->isRefunded()) {
            throw new \Exception('Payment is already refunded');
        }

        $refundAmount = $amount ?? $payment->amount;

        if ($refundAmount > $payment->amount) {
            throw new \Exception('Refund amount exceeds payment amount');
        }

        // Process refund with Stripe
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

            $refund = $stripe->refunds->create([
                'charge' => $payment->stripe_charge_id,
                'amount' => (int) ($refundAmount * 100),
                'metadata' => [
                    'payment_id' => $payment->id,
                    'reason' => $reason ?? 'Refund requested',
                ],
            ]);

            $payment->update([
                'status' => $refundAmount >= $payment->amount ? 'refunded' : 'partially_refunded',
            ]);

            Log::info('Payment refunded successfully', [
                'payment_id' => $payment->id,
                'amount' => $refundAmount,
                'refund_id' => $refund->id,
            ]);

            return $refund;
        } catch (\Exception $e) {
            Log::error('Refund failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
