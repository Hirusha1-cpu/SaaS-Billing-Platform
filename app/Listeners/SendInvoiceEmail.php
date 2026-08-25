<?php

namespace App\Listeners;

use App\Events\InvoiceSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendInvoiceEmail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(InvoiceSent $event): void
    {
        //
    }
}
