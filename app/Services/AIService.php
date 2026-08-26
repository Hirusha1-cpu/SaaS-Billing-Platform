<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Ai as LaravelAi;

class AIService
{
    protected $model;

    public function __construct()
    {
        $this->model = config('ai.drivers.openai.model', 'gpt-4o-mini');
    }

    public function parseInvoicePrompt($prompt)
    {
        try {
            $response = LaravelAi::chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an invoice parsing assistant. Extract customer name, email, items (description, quantity, price), and due date from the user input. Return as JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $content = $response->choices[0]->message->content;
            $parsed = json_decode($content, true);

            return [
                'customer_name' => $parsed['customer_name'] ?? 'Unknown Customer',
                'customer_email' => $parsed['customer_email'] ?? 'unknown@example.com',
                'items' => $parsed['items'] ?? [],
                'due_date' => isset($parsed['due_date']) ? now()->parse($parsed['due_date']) : now()->addDays(30),
            ];

        } catch (\Exception $e) {
            Log::error('AI Parse Invoice Prompt failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function generateReminderEmail($invoice)
    {
        try {
            $response = LaravelAi::chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional email writer. Write a polite reminder email for an overdue invoice.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Invoice #{$invoice->invoice_number} for {$invoice->customer->name} is overdue. Amount: {$invoice->currency} {$invoice->balance_due}. Due date: {$invoice->due_date->format('Y-m-d')}. Write a professional reminder email."
                    ]
                ],
            ]);

            return [
                'subject' => 'Reminder: Invoice #' . $invoice->invoice_number . ' is Overdue',
                'body' => $response->choices[0]->message->content,
            ];

        } catch (\Exception $e) {
            Log::error('AI Generate Reminder failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function generateInsights($data)
    {
        try {
            $response = LaravelAi::chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a business analyst. Provide insights on the invoice data. Keep it brief and professional.'
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode($data)
                    ]
                ],
            ]);

            return $response->choices[0]->message->content;

        } catch (\Exception $e) {
            Log::error('AI Generate Insights failed', [
                'error' => $e->getMessage(),
            ]);
            return 'Unable to generate insights at this time.';
        }
    }

    public function parseDocument($text)
    {
        try {
            $response = LaravelAi::chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Extract invoice information from the text. Return as JSON with: customer_name, customer_email, items (description, quantity, price), invoice_number, due_date, total_amount.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $text
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $content = $response->choices[0]->message->content;
            return json_decode($content, true);

        } catch (\Exception $e) {
            Log::error('AI Parse Document failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function suggestItems($description)
    {
        try {
            $response = LaravelAi::chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Suggest common items for an invoice based on the description. Return as JSON array with: description, quantity, unit_price.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $description
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $content = $response->choices[0]->message->content;
            $suggestions = json_decode($content, true);

            return $suggestions['items'] ?? [];

        } catch (\Exception $e) {
            Log::error('AI Suggest Items failed', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function generateInvoiceDescription($items)
    {
        try {
            $response = LaravelAi::chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Generate a professional invoice description/summary based on the items.'
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode($items)
                    ]
                ],
            ]);

            return $response->choices[0]->message->content;

        } catch (\Exception $e) {
            Log::error('AI Generate Description failed', [
                'error' => $e->getMessage(),
            ]);
            return 'Invoice generated via AI';
        }
    }
}