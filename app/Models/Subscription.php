<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\MultiTenantTrait;

class Subscription extends Model
{
    use SoftDeletes, MultiTenantTrait;

    protected $fillable = [
        'company_id',
        'customer_id',
        'name',
        'description',
        'amount',
        'currency',
        'billing_cycle',
        'billing_period',
        'start_date',
        'end_date',
        'next_billing_date',
        'status',
        'trial_ends_at',
        'cancelled_at',
        'stripe_subscription_id',
        'stripe_price_id',
        'is_active',
        'meta_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'trial_ends_at' => 'date',
        'cancelled_at' => 'datetime',
        'is_active' => 'boolean',
        'meta_data' => 'array',
    ];

    const BILLING_CYCLES = [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
    ];

    const STATUSES = [
        'active' => 'Active',
        'trialing' => 'Trialing',
        'past_due' => 'Past Due',
        'cancelled' => 'Cancelled',
        'paused' => 'Paused',
        'expired' => 'Expired',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('is_active', true);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('next_billing_date', today())
            ->where('status', 'active');
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->whereDate('end_date', '<=', now()->addDays($days))
            ->where('status', 'active');
    }

    // Helpers
    public function isActive()
    {
        return $this->status === 'active' && $this->is_active;
    }

    public function isPaused()
    {
        return $this->status === 'paused';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isTrialing()
    {
        return $this->status === 'trialing';
    }

    public function shouldBillToday()
    {
        return $this->next_billing_date && 
            $this->next_billing_date->isToday() && 
            $this->isActive();
    }

    public function getBillingCycleLabel()
    {
        return self::BILLING_CYCLES[$this->billing_cycle] ?? $this->billing_cycle;
    }

    public function getStatusLabel()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getNextBillingDateLabel()
    {
        return $this->next_billing_date ? $this->next_billing_date->format('Y-m-d') : 'N/A';
    }

    public function getTotalInvoices()
    {
        return $this->invoices()->count();
    }

    public function getTotalPaid()
    {
        return $this->invoices()->where('status', 'paid')->sum('total');
    }
}