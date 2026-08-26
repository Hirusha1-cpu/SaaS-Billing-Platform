<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\MultiTenantTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use SoftDeletes, MultiTenantTrait, HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'tax_id',
        'company_name',
        'website',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function getTotalInvoices()
    {
        return $this->invoices()->count();
    }

    public function getTotalPaid()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getOutstandingBalance()
    {
        return $this->invoices()
            ->whereNotIn('status', ['paid', 'cancelled', 'refunded'])
            ->sum('balance_due');
    }
}