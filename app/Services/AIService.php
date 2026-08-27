<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AIService
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Parse invoice prompt using Gemini
     */
    public function parseInvoicePrompt($prompt)
    {
        try {
            return $this->geminiService->parseInvoicePrompt($prompt);
        } catch (\Exception $e) {
            Log::error('AI Parse Invoice Prompt failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate reminder email using Gemini
     */
    public function generateReminderEmail($invoice)
    {
        try {
            return $this->geminiService->generateReminderEmail($invoice);
        } catch (\Exception $e) {
            Log::error('AI Generate Reminder failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate insights using Gemini
     */
    public function generateInsights($data)
    {
        try {
            return $this->geminiService->generateInsights($data);
        } catch (\Exception $e) {
            Log::error('AI Generate Insights failed: ' . $e->getMessage());
            return 'Unable to generate insights at this time.';
        }
    }

    /**
     * Parse document using Gemini
     */
    public function parseDocument($text)
    {
        try {
            return $this->geminiService->parseDocument($text);
        } catch (\Exception $e) {
            Log::error('AI Parse Document failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Suggest items using Gemini
     */
    public function suggestItems($description)
    {
        try {
            return $this->geminiService->suggestItems($description);
        } catch (\Exception $e) {
            Log::error('AI Suggest Items failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate invoice description using Gemini
     */
    public function generateInvoiceDescription($items)
    {
        try {
            return $this->geminiService->generateInvoiceDescription($items);
        } catch (\Exception $e) {
            Log::error('AI Generate Description failed: ' . $e->getMessage());
            return 'Invoice generated via AI';
        }
    }
}