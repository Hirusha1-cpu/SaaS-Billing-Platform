<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\MultiTenantTrait;
use App\Traits\AuditLogTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use MultiTenantTrait, AuditLogTrait, HasFactory;

    protected $fillable = [
        'company_id',
        'invoice_id',
        'customer_id',
        'amount',
        'currency',
        'payment_method',
        'status',
        'transaction_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'payment_date',
        'receipt_url',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    const STATUSES = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        'partially_refunded' => 'Partially Refunded',
    ];

    const METHODS = [
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
        'bank_transfer' => 'Bank Transfer',
        'cash' => 'Cash',
        'check' => 'Check',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Helpers
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isRefunded()
    {
        return $this->status === 'refunded';
    }

    public function getStatusLabel()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getMethodLabel()
    {
        return self::METHODS[$this->payment_method] ?? $this->payment_method;
    }
}