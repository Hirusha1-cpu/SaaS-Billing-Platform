<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Company;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $customers = Customer::all();

        if ($company && $customers->count() > 0) {
            // Create invoices for each customer
            foreach ($customers as $customer) {
                // Draft Invoice
                $invoice = Invoice::create([
                    'company_id' => $company->id,
                    'customer_id' => $customer->id,
                    'invoice_number' => Invoice::generateInvoiceNumber($company->id),
                    'issue_date' => now(),
                    'due_date' => now()->addDays(30),
                    'subtotal' => 15000.00,
                    'tax' => 2250.00,
                    'tax_rate' => 15.00,
                    'discount' => 0,
                    'total' => 17250.00,
                    'paid_amount' => 0,
                    'balance_due' => 17250.00,
                    'status' => 'draft',
                    'currency' => 'LKR',
                    'notes' => 'Test draft invoice',
                ]);

                // Add items
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'company_id' => $company->id,
                    'description' => 'Web Development Service',
                    'quantity' => 1,
                    'unit_price' => 15000.00,
                    'tax_rate' => 15.00,
                    'discount' => 0,
                    'total' => 15000.00,
                ]);

                // Sent Invoice
                $invoice2 = Invoice::create([
                    'company_id' => $company->id,
                    'customer_id' => $customer->id,
                    'invoice_number' => Invoice::generateInvoiceNumber($company->id),
                    'issue_date' => now()->subDays(10),
                    'due_date' => now()->addDays(20),
                    'subtotal' => 25000.00,
                    'tax' => 3750.00,
                    'tax_rate' => 15.00,
                    'discount' => 1000.00,
                    'total' => 27750.00,
                    'paid_amount' => 0,
                    'balance_due' => 27750.00,
                    'status' => 'sent',
                    'currency' => 'LKR',
                    'notes' => 'Sent invoice',
                    'sent_at' => now()->subDays(10),
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice2->id,
                    'company_id' => $company->id,
                    'description' => 'Mobile App Development',
                    'quantity' => 1,
                    'unit_price' => 25000.00,
                    'tax_rate' => 15.00,
                    'discount' => 1000.00,
                    'total' => 25000.00,
                ]);

                // Paid Invoice
                $invoice3 = Invoice::create([
                    'company_id' => $company->id,
                    'customer_id' => $customer->id,
                    'invoice_number' => Invoice::generateInvoiceNumber($company->id),
                    'issue_date' => now()->subDays(30),
                    'due_date' => now()->subDays(5),
                    'subtotal' => 10000.00,
                    'tax' => 1500.00,
                    'tax_rate' => 15.00,
                    'discount' => 0,
                    'total' => 11500.00,
                    'paid_amount' => 11500.00,
                    'balance_due' => 0,
                    'status' => 'paid',
                    'currency' => 'LKR',
                    'notes' => 'Paid invoice',
                    'sent_at' => now()->subDays(30),
                    'paid_at' => now()->subDays(5),
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice3->id,
                    'company_id' => $company->id,
                    'description' => 'Consulting Service',
                    'quantity' => 2,
                    'unit_price' => 5000.00,
                    'tax_rate' => 15.00,
                    'discount' => 0,
                    'total' => 10000.00,
                ]);
            }
        }
    }
}