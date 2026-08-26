<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Invoice;

class InvoicePolicy
{
    /**
     * Determine if the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'accountant', 'viewer']);
    }

    /**
     * Determine if the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Check if invoice belongs to user's company
        if ($user->company_id !== $invoice->company_id) {
            return false;
        }

        return in_array($user->role, ['admin', 'accountant', 'viewer']);
    }

    /**
     * Determine if the user can create invoices.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'accountant']);
    }

    /**
     * Determine if the user can update the invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Only draft invoices can be edited
        if (!$invoice->isDraft()) {
            return false;
        }

        // Check if invoice belongs to user's company
        if ($user->company_id !== $invoice->company_id) {
            return false;
        }

        return in_array($user->role, ['admin', 'accountant']);
    }

    /**
     * Determine if the user can delete the invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Only draft invoices can be deleted
        if (!$invoice->isDraft()) {
            return false;
        }

        // Check if invoice belongs to user's company
        if ($user->company_id !== $invoice->company_id) {
            return false;
        }

        return $user->role === 'admin';
    }

    /**
     * Determine if the user can send the invoice.
     */
    public function send(User $user, Invoice $invoice): bool
    {
        // Check if invoice belongs to user's company
        if ($user->company_id !== $invoice->company_id) {
            return false;
        }

        return in_array($user->role, ['admin', 'accountant']);
    }

    /**
     * Determine if the user can mark invoice as paid.
     */
    public function markPaid(User $user, Invoice $invoice): bool
    {
        // Check if invoice belongs to user's company
        if ($user->company_id !== $invoice->company_id) {
            return false;
        }

        return in_array($user->role, ['admin', 'accountant']);
    }
}