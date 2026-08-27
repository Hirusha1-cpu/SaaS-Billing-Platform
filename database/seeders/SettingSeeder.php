<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Company;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        if ($company) {
            $settings = [
                [
                    'key' => 'invoice_prefix',
                    'value' => 'INV',
                    'group' => 'invoice',
                ],
                [
                    'key' => 'default_due_days',
                    'value' => '30',
                    'group' => 'invoice',
                ],
                [
                    'key' => 'default_tax_rate',
                    'value' => '15',
                    'group' => 'invoice',
                ],
                [
                    'key' => 'default_currency',
                    'value' => 'LKR',
                    'group' => 'invoice',
                ],
                [
                    'key' => 'invoice_notes',
                    'value' => 'Thank you for your business!',
                    'group' => 'invoice',
                ],
                [
                    'key' => 'invoice_terms',
                    'value' => 'Payment due within 30 days',
                    'group' => 'invoice',
                ],
                [
                    'key' => 'email_notifications',
                    'value' => json_encode([
                        'invoice_sent' => true,
                        'payment_received' => true,
                        'overdue_reminder' => true,
                    ]),
                    'group' => 'notification',
                ],
            ];

            foreach ($settings as $setting) {
                Setting::create(array_merge($setting, [
                    'company_id' => $company->id,
                    'is_public' => true,
                ]));
            }
        }
    }
}