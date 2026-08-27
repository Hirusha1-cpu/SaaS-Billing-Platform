<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Company;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $paidInvoices = Invoice::where('status', 'paid')->get();

        if ($company && $paidInvoices->count() > 0) {
            foreach ($paidInvoices as $invoice) {
                Payment::create([
                    'company_id' => $company->id,
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'amount' => $invoice->total,
                    'currency' => $invoice->currency,
                    'payment_method' => 'stripe',
                    'status' => 'completed',
                    'transaction_id' => 'txn_' . uniqid(),
                    'payment_date' => $invoice->paid_at ?? now(),
                    'notes' => 'Payment for invoice #' . $invoice->invoice_number,
                ]);
            }

            // Partially paid invoice
            $sentInvoice = Invoice::where('status', 'sent')->first();
            if ($sentInvoice) {
                Payment::create([
                    'company_id' => $company->id,
                    'invoice_id' => $sentInvoice->id,
                    'customer_id' => $sentInvoice->customer_id,
                    'amount' => $sentInvoice->total * 0.5,
                    'currency' => $sentInvoice->currency,
                    'payment_method' => 'bank_transfer',
                    'status' => 'completed',
                    'transaction_id' => 'txn_' . uniqid(),
                    'payment_date' => now(),
                    'notes' => 'Partial payment',
                ]);
            }
        }
    }
}