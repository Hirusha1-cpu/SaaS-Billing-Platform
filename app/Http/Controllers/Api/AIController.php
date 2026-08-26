<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Customer;
use App\Services\AIService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AIController extends Controller
{
    protected $aiService;
    protected $invoiceService;

    public function __construct(AIService $aiService, InvoiceService $invoiceService)
    {
        $this->aiService = $aiService;
        $this->invoiceService = $invoiceService;
    }

    public function generateInvoice(Request $request)
    {
        $validator = validator($request->all(), [
            'prompt' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Parse prompt using AI
            $parsedData = $this->aiService->parseInvoicePrompt($request->prompt);

            // Find or create customer
            $customer = Customer::firstOrCreate(
                ['email' => $parsedData['customer_email']],
                [
                    'name' => $parsedData['customer_name'],
                    'email' => $parsedData['customer_email'],
                    'company_id' => Auth::user()->company_id,
                ]
            );

            // Create invoice
            $invoiceData = [
                'customer_id' => $customer->id,
                'issue_date' => now(),
                'due_date' => $parsedData['due_date'] ?? now()->addDays(30),
                'tax_rate' => Auth::user()->company->tax_rate ?? 15,
                'status' => 'draft',
                'created_by' => Auth::id(),
                'items' => $parsedData['items'],
            ];

            $invoice = $this->invoiceService->createInvoice($invoiceData);

            DB::commit();

            return response()->json([
                'message' => 'Invoice generated from AI prompt successfully',
                'invoice_id' => $invoice->id,
                'invoice' => $invoice->load(['customer', 'items']),
                'parsed_data' => $parsedData,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function generateReminder(Request $request, Invoice $invoice)
    {
        // $this->authorize('view', $invoice);
        Gate::authorize('view', $invoice);

        try {
            $reminder = $this->aiService->generateReminderEmail($invoice);

            return response()->json([
                'message' => 'Reminder generated successfully',
                'reminder' => $reminder,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getInsights(Request $request)
    {
        try {
            $data = [
                'total_invoices' => Invoice::count(),
                'total_revenue' => Invoice::where('status', 'paid')->sum('total'),
                'overdue_count' => Invoice::where('status', 'overdue')->count(),
                'pending_count' => Invoice::whereIn('status', ['sent', 'partially_paid'])->count(),
                'top_customers' => Customer::withCount('invoices')
                    ->withSum('payments', 'amount')
                    ->orderBy('payments_sum_amount', 'desc')
                    ->limit(5)
                    ->get(),
            ];

            $insights = $this->aiService->generateInsights($data);

            return response()->json([
                'data' => $data,
                'insights' => $insights,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function parseDocument(Request $request)
    {
        $validator = validator($request->all(), [
            'text' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $parsed = $this->aiService->parseDocument($request->text);

            return response()->json([
                'parsed_data' => $parsed,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function suggestItems(Request $request)
    {
        $validator = validator($request->all(), [
            'description' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $suggestions = $this->aiService->suggestItems($request->description);

            return response()->json([
                'suggestions' => $suggestions,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}