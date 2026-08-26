<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\MultiTenantTrait;

class InvoiceItem extends Model
{
    use MultiTenantTrait;

    protected $fillable = [
        'invoice_id',
        'company_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount',
        'total',
        'order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Helpers
    public function calculateTotal()
    {
        $subtotal = $this->quantity * $this->unit_price;
        $tax = $subtotal * ($this->tax_rate / 100);
        $discount = $subtotal * ($this->discount / 100);
        return $subtotal + $tax - $discount;
    }

    // Auto calculate total
    protected static function booted()
    {
        static::saving(function ($item) {
            $item->total = $item->calculateTotal();
        });
    }
}