<?php

namespace App\Listeners;

use App\Events\SecurityAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogSecurityAlert
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
    public function handle(SecurityAlert $event): void
    {
        //
    }
}
