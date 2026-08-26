<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Traits\InvoiceCalculationsTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    use InvoiceCalculationsTrait;

    public function createInvoice(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Calculate totals
            $items = $data['items'] ?? [];
            $subtotal = 0;
            $taxRate = $data['tax_rate'] ?? 15;

            foreach ($items as &$item) {
                $itemTotal = $this->calculateItemTotal($item['quantity'], $item['unit_price']);
                $subtotal += $itemTotal;
                $item['total'] = $itemTotal;
            }

            $tax = $this->calculateTax($subtotal, $taxRate);
            $total = $subtotal + $tax - ($data['discount'] ?? 0);

            // Create invoice
            $invoice = Invoice::create([
                'company_id' => Auth::user()->company_id,
                'customer_id' => $data['customer_id'],
                'issue_date' => $data['issue_date'] ?? now(),
                'due_date' => $data['due_date'] ?? now()->addDays(30),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'tax_rate' => $taxRate,
                'discount' => $data['discount'] ?? 0,
                'total' => $total,
                'balance_due' => $total,
                'status' => 'draft',
                'currency' => $data['currency'] ?? 'LKR',
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Create items
            foreach ($items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'company_id' => Auth::user()->company_id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'total' => $item['total'],
                    'order' => $item['order'] ?? 0,
                ]);
            }

            Log::info('Invoice created successfully', [
                'invoice_id' => $invoice->id,
                'created_by' => Auth::id(),
            ]);

            return $invoice;
        });
    }

    public function updateInvoice(Invoice $invoice, array $data)
    {
        return DB::transaction(function () use ($invoice, $data) {
            // Recalculate totals
            $items = $data['items'] ?? [];
            $subtotal = 0;
            $taxRate = $data['tax_rate'] ?? $invoice->tax_rate;

            // Delete existing items
            $invoice->items()->delete();

            // Create new items
            foreach ($items as $item) {
                $itemTotal = $this->calculateItemTotal($item['quantity'], $item['unit_price']);
                $subtotal += $itemTotal;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'company_id' => Auth::user()->company_id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'total' => $itemTotal,
                    'order' => $item['order'] ?? 0,
                ]);
            }

            $tax = $this->calculateTax($subtotal, $taxRate);
            $total = $subtotal + $tax - ($data['discount'] ?? $invoice->discount ?? 0);

            // Update invoice
            $invoice->update([
                'customer_id' => $data['customer_id'] ?? $invoice->customer_id,
                'issue_date' => $data['issue_date'] ?? $invoice->issue_date,
                'due_date' => $data['due_date'] ?? $invoice->due_date,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'tax_rate' => $taxRate,
                'discount' => $data['discount'] ?? $invoice->discount,
                'total' => $total,
                'balance_due' => $this->calculateBalanceDue($invoice),
                'currency' => $data['currency'] ?? $invoice->currency,
                'notes' => $data['notes'] ?? $invoice->notes,
                'terms' => $data['terms'] ?? $invoice->terms,
            ]);

            Log::info('Invoice updated successfully', [
                'invoice_id' => $invoice->id,
                'updated_by' => Auth::id(),
            ]);

            return $invoice;
        });
    }

    public function sendInvoice(Invoice $invoice)
    {
        if ($invoice->isSent() || $invoice->isPaid()) {
            throw new \Exception('Invoice has already been sent or paid');
        }

        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Send email to customer (implement later)
        Log::info('Invoice sent successfully', [
            'invoice_id' => $invoice->id,
            'sent_by' => Auth::id(),
        ]);

        return $invoice;
    }

    public function markAsPaid(Invoice $invoice, $amount)
    {
        if ($invoice->isPaid()) {
            throw new \Exception('Invoice is already paid');
        }

        $balance = $invoice->balance_due - $amount;
        $status = $balance <= 0 ? 'paid' : 'partially_paid';

        $invoice->update([
            'status' => $status,
            'paid_amount' => $invoice->paid_amount + $amount,
            'balance_due' => max(0, $balance),
            'paid_at' => $status === 'paid' ? now() : null,
        ]);

        Log::info('Invoice marked as paid', [
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'status' => $status,
        ]);

        return $invoice;
    }

    public function duplicateInvoice(Invoice $invoice)
    {
        return DB::transaction(function () use ($invoice) {
            // Create new invoice
            $newInvoice = Invoice::create([
                'company_id' => Auth::user()->company_id,
                'customer_id' => $invoice->customer_id,
                'invoice_number' => Invoice::generateInvoiceNumber(Auth::user()->company_id),
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'subtotal' => $invoice->subtotal,
                'tax' => $invoice->tax,
                'tax_rate' => $invoice->tax_rate,
                'discount' => $invoice->discount,
                'total' => $invoice->total,
                'balance_due' => $invoice->total,
                'status' => 'draft',
                'currency' => $invoice->currency,
                'notes' => $invoice->notes,
                'terms' => $invoice->terms,
                'created_by' => Auth::id(),
            ]);

            // Duplicate items
            foreach ($invoice->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $newInvoice->id,
                    'company_id' => Auth::user()->company_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_rate' => $item->tax_rate,
                    'discount' => $item->discount,
                    'total' => $item->total,
                    'order' => $item->order,
                ]);
            }

            Log::info('Invoice duplicated successfully', [
                'original_invoice_id' => $invoice->id,
                'new_invoice_id' => $newInvoice->id,
            ]);

            return $newInvoice;
        });
    }

    public function checkOverdueInvoices()
    {
        $overdueInvoices = Invoice::whereIn('status', ['sent', 'partially_paid'])
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $invoice->status = 'overdue';
            $invoice->save();

            Log::info('Invoice marked as overdue', [
                'invoice_id' => $invoice->id,
                'due_date' => $invoice->due_date,
            ]);
        }

        return $overdueInvoices->count();
    }
}