<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Invoice;

class InvoiceLocked
{
    public function handle(Request $request, Closure $next, $invoiceId = null)
    {
        $invoice = $request->route('invoice') ?? Invoice::find($invoiceId);

        if ($invoice && !$invoice->isDraft()) {
            return response()->json([
                'error' => 'This invoice is locked and cannot be edited',
                'status' => $invoice->status,
            ], 403);
        }

        return $next($request);
    }
}
