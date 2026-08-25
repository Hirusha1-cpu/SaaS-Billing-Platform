<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:clean-audit-logs')]
#[Description('Command description')]
class CleanAuditLogs extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
