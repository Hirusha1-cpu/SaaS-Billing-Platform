<?php

return [
    'prefix' => env('INVOICE_PREFIX', 'INV'),
    
    'default_status' => 'draft',
    
    'tax_rates' => [
        'default' => env('DEFAULT_TAX_RATE', 15),
    ],
    
    'statuses' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'partially_paid' => 'Partially Paid',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ],
    
    'due_days' => env('DEFAULT_DUE_DAYS', 30),
    
    'auto_overdue' => true,
    
    'allow_partial_payments' => true,
];
