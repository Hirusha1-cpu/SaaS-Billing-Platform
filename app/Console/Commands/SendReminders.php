<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\AIService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:send-reminders')]
#[Description('Command description')]
class SendReminders extends Command
{
    protected $signature = 'invoices:send-reminders';
    protected $description = 'Send reminders for overdue invoices';

    protected $aiService;

    public function __construct(AIService $aiService)
    {
        parent::__construct();
        $this->aiService = $aiService;
    }

    public function handle()
    {
        $this->info('Sending overdue reminders...');

        $overdueInvoices = Invoice::where('status', 'overdue')
            ->whereNull('reminder_sent_at')
            ->get();

        $sent = 0;

        foreach ($overdueInvoices as $invoice) {
            try {
                // Generate reminder using AI
                $reminder = $this->aiService->generateReminderEmail($invoice);

                // Send email (implement your email sending logic)
                // Mail::to($invoice->customer->email)->send(new ReminderMail($invoice, $reminder));

                $invoice->update(['reminder_sent_at' => now()]);
                $sent++;

                $this->info("Reminder sent for invoice #{$invoice->invoice_number}");

            } catch (\Exception $e) {
                $this->error("Failed to send reminder for invoice #{$invoice->invoice_number}: {$e->getMessage()}");
                Log::error('Reminder sending failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$sent} reminders");
        Log::info('Reminders sent', ['sent' => $sent]);

        return Command::SUCCESS;
    }

}
