<?php

namespace App\Traits;

trait InvoiceCalculationsTrait
{
    public function calculateInvoiceTotals($invoice)
    {
        $items = $invoice->items;
        
        $subtotal = $items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
        
        $tax = $subtotal * ($invoice->tax_rate / 100);
        
        $discount = $invoice->discount ?? 0;
        
        $total = $subtotal + $tax - $discount;
        
        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'discount' => round($discount, 2),
            'total' => round($total, 2),
        ];
    }

    public function calculateBalanceDue($invoice)
    {
        $paidAmount = $invoice->payments()
            ->where('status', 'completed')
            ->sum('amount');
            
        return max(0, $invoice->total - $paidAmount);
    }

    public function calculateTax($subtotal, $taxRate)
    {
        return $subtotal * ($taxRate / 100);
    }

    public function calculateItemTotal($quantity, $unitPrice)
    {
        return $quantity * $unitPrice;
    }
}