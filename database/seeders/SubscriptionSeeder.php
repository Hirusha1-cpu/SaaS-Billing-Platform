<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\Customer;
use App\Models\Company;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $customers = Customer::all();

        if ($company && $customers->count() > 0) {
            $subscriptions = [
                [
                    'customer_id' => $customers->first()->id,
                    'name' => 'Monthly Premium Service',
                    'description' => 'Premium monthly service subscription',
                    'amount' => 25000.00,
                    'currency' => 'LKR',
                    'billing_cycle' => 'monthly',
                    'billing_period' => 1,
                    'start_date' => now(),
                    'next_billing_date' => now()->addMonth(),
                    'status' => 'active',
                    'is_active' => true,
                ],
                [
                    'customer_id' => $customers->skip(1)->first()->id,
                    'name' => 'Yearly Enterprise Plan',
                    'description' => 'Enterprise yearly subscription with full features',
                    'amount' => 250000.00,
                    'currency' => 'LKR',
                    'billing_cycle' => 'yearly',
                    'billing_period' => 1,
                    'start_date' => now()->subMonths(2),
                    'end_date' => now()->addMonths(10),
                    'next_billing_date' => now()->addMonths(10),
                    'status' => 'active',
                    'is_active' => true,
                ],
                [
                    'customer_id' => $customers->skip(2)->first()->id,
                    'name' => 'Quarterly Business Plan',
                    'description' => 'Business plan billed quarterly',
                    'amount' => 75000.00,
                    'currency' => 'LKR',
                    'billing_cycle' => 'quarterly',
                    'billing_period' => 1,
                    'start_date' => now()->subMonths(1),
                    'next_billing_date' => now()->addMonths(2),
                    'status' => 'active',
                    'is_active' => true,
                ],
                [
                    'customer_id' => $customers->skip(3)->first()->id,
                    'name' => 'Monthly Basic Plan',
                    'description' => 'Basic monthly subscription',
                    'amount' => 10000.00,
                    'currency' => 'LKR',
                    'billing_cycle' => 'monthly',
                    'billing_period' => 1,
                    'start_date' => now(),
                    'next_billing_date' => now()->addMonth(),
                    'status' => 'paused',
                    'is_active' => false,
                ],
            ];

            foreach ($subscriptions as $subscription) {
                Subscription::create(array_merge($subscription, [
                    'company_id' => $company->id,
                ]));
            }
        }
    }
}