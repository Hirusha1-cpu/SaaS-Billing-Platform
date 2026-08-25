<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-reminders')]
#[Description('Command description')]
class SendReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
