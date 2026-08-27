<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $model;
    protected $availableModels;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        
        // Updated available models (2026)
        $this->availableModels = [
            'gemini-3.7-flash',
            'gemini-3.6-flash',
            'gemini-3.5-flash',
            'gemini-2.5-pro',
            'gemini-2.5-flash-lite',
        ];
        
        $this->model = $this->getWorkingModel();
    }

    /**
     * Get working model from available models
     */
    public function getWorkingModel()
    {
        // Try to get from env
        $envModel = env('GEMINI_MODEL');
        if ($envModel && in_array($envModel, $this->availableModels)) {
            return $envModel;
        }

        // Try to get from API
        try {
            $models = $this->listModels();
            foreach ($this->availableModels as $preferred) {
                foreach ($models as $model) {
                    $modelName = $model['name'] ?? '';
                    if (str_contains($modelName, $preferred)) {
                        Log::info('Using Gemini model: ' . $preferred);
                        return $preferred;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not fetch models: ' . $e->getMessage());
        }

        // Fallback to latest stable
        return 'gemini-3.6-flash';
    }

    /**
     * Get available models from Gemini API
     */
    public function listModels()
    {
        if (empty($this->apiKey)) {
            return [];
        }

        $url = "https://generativelanguage.googleapis.com/v1/models?key={$this->apiKey}";
        
        try {
            $response = Http::get($url);
            
            if ($response->successful()) {
                return $response->json()['models'] ?? [];
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch models: ' . $e->getMessage());
        }
        
        return [];
    }

    /**
     * Generate content using Gemini API
     */
    public function generateContent($prompt, $systemPrompt = null)
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API key is missing');
            throw new \Exception('Gemini API key is not configured. Please add GEMINI_API_KEY to .env');
        }

        // Use model name directly (without "models/" prefix)
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $contents = [];

        // Add system prompt if provided
        if ($systemPrompt) {
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => "System: " . $systemPrompt]
                ]
            ];
        }

        // Add user prompt
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $prompt]
            ]
        ];

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
                'topP' => 0.95,
                'topK' => 64,
            ],
        ];

        try {
            Log::info('Calling Gemini API with model: ' . $this->model);
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->failed()) {
                Log::error('Gemini API call failed: ' . $response->body());
                throw new \Exception('Gemini API call failed: ' . $response->body());
            }

            $data = $response->json();
            
            // Extract text from response
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return [
                'success' => true,
                'text' => $text,
                'raw' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('Gemini API error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate JSON response from Gemini
     */
    public function generateJson($prompt, $systemPrompt = null)
    {
        $system = $systemPrompt ?? 'Return only valid JSON. Do not include any other text.';
        
        $response = $this->generateContent($prompt, $system);
        
        if (!$response['success']) {
            return null;
        }

        // Extract JSON from response
        $text = $response['text'];
        
        // Try to find JSON in the response
        preg_match('/\{[\s\S]*\}/', $text, $matches);
        
        if (!empty($matches)) {
            return json_decode($matches[0], true);
        }

        // Try to parse the entire response as JSON
        return json_decode($text, true);
    }

    /**
     * Parse invoice prompt using Gemini
     */
    public function parseInvoicePrompt($prompt)
    {
        $systemPrompt = 'You are an invoice parsing assistant. Extract customer name, email, items (description, quantity, price), and due date from the user input. Return as JSON with keys: customer_name, customer_email, items (array with description, quantity, unit_price), due_date (YYYY-MM-DD format).';
        
        $result = $this->generateJson($prompt, $systemPrompt);

        if (!$result) {
            return [
                'customer_name' => 'Unknown Customer',
                'customer_email' => 'unknown@example.com',
                'items' => [],
                'due_date' => now()->addDays(30)->format('Y-m-d'),
            ];
        }

        return [
            'customer_name' => $result['customer_name'] ?? 'Unknown Customer',
            'customer_email' => $result['customer_email'] ?? 'unknown@example.com',
            'items' => $result['items'] ?? [],
            'due_date' => $result['due_date'] ?? now()->addDays(30)->format('Y-m-d'),
        ];
    }

    /**
     * Generate reminder email using Gemini
     */
    public function generateReminderEmail($invoice)
    {
        $prompt = "Invoice #{$invoice->invoice_number} for {$invoice->customer->name} is overdue. Amount: {$invoice->currency} {$invoice->balance_due}. Due date: {$invoice->due_date->format('Y-m-d')}. Write a professional reminder email.";
        
        $systemPrompt = 'You are a professional email writer. Write a polite reminder email for an overdue invoice. Keep it professional and courteous.';
        
        $response = $this->generateContent($prompt, $systemPrompt);

        return [
            'subject' => 'Reminder: Invoice #' . $invoice->invoice_number . ' is Overdue',
            'body' => $response['text'] ?? 'Please pay your overdue invoice.',
        ];
    }

    /**
     * Generate insights using Gemini
     */
    public function generateInsights($data)
    {
        $prompt = "Analyze this invoice data and provide business insights:\n\n" . json_encode($data, JSON_PRETTY_PRINT);
        
        $systemPrompt = 'You are a business analyst. Provide insights on the invoice data. Keep it brief, professional, and actionable.';
        
        $response = $this->generateContent($prompt, $systemPrompt);

        return $response['text'] ?? 'Unable to generate insights at this time.';
    }

    /**
     * Parse document using Gemini
     */
    public function parseDocument($text)
    {
        $systemPrompt = 'Extract invoice information from the text. Return as JSON with: customer_name, customer_email, items (array with description, quantity, unit_price), invoice_number, due_date (YYYY-MM-DD), total_amount.';
        
        $result = $this->generateJson($text, $systemPrompt);

        return $result ?? [
            'customer_name' => 'Unknown',
            'customer_email' => 'unknown@example.com',
            'items' => [],
            'invoice_number' => 'N/A',
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'total_amount' => 0,
        ];
    }

    /**
     * Suggest items using Gemini
     */
    public function suggestItems($description)
    {
        $systemPrompt = 'Suggest common items for an invoice based on the description. Return as JSON with "items" array containing: description, quantity, unit_price.';
        
        $result = $this->generateJson($description, $systemPrompt);

        return $result['items'] ?? [];
    }

    /**
     * Generate invoice description using Gemini
     */
    public function generateInvoiceDescription($items)
    {
        $prompt = "Generate a professional invoice description/summary based on these items:\n\n" . json_encode($items, JSON_PRETTY_PRINT);
        
        $systemPrompt = 'Generate a professional invoice description/summary. Keep it concise and professional.';
        
        $response = $this->generateContent($prompt, $systemPrompt);

        return $response['text'] ?? 'Invoice generated via AI';
    }

    /**
     * Get the current model being used
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set model dynamically
     */
    public function setModel($model)
    {
        if (in_array($model, $this->availableModels)) {
            $this->model = $model;
            return true;
        }
        return false;
    }
}