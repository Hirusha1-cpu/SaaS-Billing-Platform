<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPaymentConfirmation
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
    public function handle(PaymentReceived $event): void
    {
        //
    }
}
