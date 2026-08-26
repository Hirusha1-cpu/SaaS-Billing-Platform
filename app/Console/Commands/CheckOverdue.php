<?php

namespace App\Console\Commands;

use App\Services\InvoiceService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:check-overdue')]
#[Description('Command description')]
class CheckOverdue extends Command
{
   protected $signature = 'invoices:check-overdue';
    protected $description = 'Check and update overdue invoices';

    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        parent::__construct();
        $this->invoiceService = $invoiceService;
    }

    public function handle()
    {
        $this->info('Checking for overdue invoices...');

        try {
            $count = $this->invoiceService->checkOverdueInvoices();

            $this->info("Found and marked {$count} invoices as overdue");

            Log::info('Overdue check completed', ['count' => $count]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Overdue check failed: ' . $e->getMessage());
            Log::error('Overdue check failed', ['error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }
}
