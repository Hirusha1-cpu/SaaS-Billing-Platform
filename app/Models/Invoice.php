<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\MultiTenantTrait;
use App\Traits\AuditLogTrait;

class Invoice extends Model
{
    use SoftDeletes, MultiTenantTrait, AuditLogTrait;

    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_number',
        'reference',
        'issue_date',
        'due_date',
        'subtotal',
        'tax',
        'tax_rate',
        'discount',
        'total',
        'paid_amount',
        'balance_due',
        'status',
        'currency',
        'notes',
        'terms',
        'created_by',
        'sent_at',
        'paid_at',
        'pdf_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    const STATUSES = [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'partially_paid' => 'Partially Paid',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
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

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function($q) {
                $q->whereIn('status', ['sent', 'partially_paid'])
                  ->where('due_date', '<', now());
            });
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Helpers
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isSent()
    {
        return $this->status === 'sent';
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isOverdue()
    {
        return $this->status === 'overdue' || 
            ($this->due_date && $this->due_date->isPast() && !$this->isPaid());
    }

    public function isLocked()
    {
        return !$this->isDraft();
    }

    public function canBeEdited()
    {
        return $this->isDraft();
    }

    public function canBeDeleted()
    {
        return $this->isDraft();
    }

    public function getRemainingBalance()
    {
        return max(0, $this->total - $this->paid_amount);
    }

    public function getStatusLabel()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColor()
    {
        $colors = [
            'draft' => 'gray',
            'sent' => 'blue',
            'partially_paid' => 'yellow',
            'paid' => 'green',
            'overdue' => 'red',
            'cancelled' => 'red',
            'refunded' => 'purple',
        ];
        return $colors[$this->status] ?? 'gray';
    }

    // Event - Auto generate invoice number
    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber($invoice->company_id);
            }
        });
    }

    public static function generateInvoiceNumber($companyId)
    {
        $prefix = 'INV-' . str_pad($companyId, 3, '0', STR_PAD_LEFT);
        $last = static::where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();
        
        if ($last && preg_match('/-(\d+)$/', $last->invoice_number, $matches)) {
            $next = (int)$matches[1] + 1;
        } else {
            $next = 1;
        }
        
        return $prefix . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}