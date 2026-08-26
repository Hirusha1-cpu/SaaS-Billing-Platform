<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:process-billing')]
#[Description('Command description')]
class ProcessBilling extends Command
{
     protected $signature = 'billing:process';
    protected $description = 'Process daily subscription billing';

    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
    }

    public function handle()
    {
        $this->info('Starting billing process...');

        try {
            $result = $this->subscriptionService->processDailyBilling();

            $this->info("Processed: {$result['processed']} subscriptions");
            $this->info("Failed: {$result['failed']} subscriptions");

            Log::info('Billing command executed successfully', $result);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Billing failed: ' . $e->getMessage());
            Log::error('Billing command failed', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
