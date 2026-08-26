<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function createSubscription(array $data)
    {
        return DB::transaction(function () use ($data) {
            $subscription = Subscription::create([
                'company_id' => Auth::user()->company_id,
                'customer_id' => $data['customer_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'LKR',
                'billing_cycle' => $data['billing_cycle'],
                'billing_period' => $data['billing_period'] ?? 1,
                'start_date' => $data['start_date'] ?? now(),
                'end_date' => $data['end_date'] ?? null,
                'next_billing_date' => $data['start_date'] ?? now(),
                'status' => 'active',
                'trial_ends_at' => $data['trial_ends_at'] ?? null,
                'is_active' => true,
                'meta_data' => $data['meta_data'] ?? null,
            ]);

            Log::info('Subscription created successfully', [
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
            ]);

            return $subscription;
        });
    }

    public function updateSubscription(Subscription $subscription, array $data)
    {
        $subscription->update($data);

        Log::info('Subscription updated successfully', [
            'subscription_id' => $subscription->id,
        ]);

        return $subscription;
    }

    public function cancelSubscription(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'cancelled',
            'is_active' => false,
            'cancelled_at' => now(),
        ]);

        Log::info('Subscription cancelled', [
            'subscription_id' => $subscription->id,
        ]);

        return $subscription;
    }

    public function pauseSubscription(Subscription $subscription)
    {
        if ($subscription->status !== 'active') {
            throw new \Exception('Only active subscriptions can be paused');
        }

        $subscription->update([
            'status' => 'paused',
        ]);

        Log::info('Subscription paused', [
            'subscription_id' => $subscription->id,
        ]);

        return $subscription;
    }

    public function resumeSubscription(Subscription $subscription)
    {
        if ($subscription->status !== 'paused') {
            throw new \Exception('Only paused subscriptions can be resumed');
        }

        $subscription->update([
            'status' => 'active',
        ]);

        Log::info('Subscription resumed', [
            'subscription_id' => $subscription->id,
        ]);

        return $subscription;
    }

    public function processDailyBilling()
    {
        $dueSubscriptions = Subscription::dueToday()->get();
        $processed = 0;
        $failed = 0;

        foreach ($dueSubscriptions as $subscription) {
            try {
                $this->processSubscriptionBilling($subscription);
                $processed++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('Subscription billing failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Daily billing processed', [
            'processed' => $processed,
            'failed' => $failed,
        ]);

        return [
            'processed' => $processed,
            'failed' => $failed,
        ];
    }

    protected function processSubscriptionBilling(Subscription $subscription)
    {
        return DB::transaction(function () use ($subscription) {
            // Create invoice
            $invoice = Invoice::create([
                'company_id' => $subscription->company_id,
                'customer_id' => $subscription->customer_id,
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'subtotal' => $subscription->amount,
                'tax' => 0,
                'tax_rate' => 0,
                'total' => $subscription->amount,
                'balance_due' => $subscription->amount,
                'status' => 'sent',
                'currency' => $subscription->currency,
                'notes' => "Subscription: {$subscription->name}",
                'created_by' => null,
                'sent_at' => now(),
            ]);

            // Create invoice item
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'company_id' => $subscription->company_id,
                'description' => $subscription->name . ' - ' . $subscription->billing_cycle . ' subscription',
                'quantity' => 1,
                'unit_price' => $subscription->amount,
                'tax_rate' => 0,
                'total' => $subscription->amount,
            ]);

            // Update next billing date
            $nextBillingDate = $this->calculateNextBillingDate($subscription);
            $subscription->update([
                'next_billing_date' => $nextBillingDate,
            ]);

            Log::info('Subscription invoice created', [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
            ]);

            return $invoice;
        });
    }

    protected function calculateNextBillingDate(Subscription $subscription)
    {
        $date = $subscription->next_billing_date ?: now();

        switch ($subscription->billing_cycle) {
            case 'daily':
                return $date->addDays($subscription->billing_period ?? 1);
            case 'weekly':
                return $date->addWeeks($subscription->billing_period ?? 1);
            case 'monthly':
                return $date->addMonths($subscription->billing_period ?? 1);
            case 'quarterly':
                return $date->addMonths($subscription->billing_period ?? 3);
            case 'yearly':
                return $date->addYears($subscription->billing_period ?? 1);
            default:
                return $date->addMonth();
        }
    }

    public function checkExpiringSubscriptions($days = 7)
    {
        $expiring = Subscription::expiringSoon($days)->get();

        foreach ($expiring as $subscription) {
            Log::info('Subscription expiring soon', [
                'subscription_id' => $subscription->id,
                'end_date' => $subscription->end_date,
                'days_remaining' => $days,
            ]);
        }

        return $expiring;
    }
}