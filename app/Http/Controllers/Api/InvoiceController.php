<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'items', 'creator']);

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . $request->search . '%')
                  ->orWhere('reference', 'like', '%' . $request->search . '%');
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(15);

        return InvoiceResource::collection($invoices);
    }

    public function store(InvoiceRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $invoice = $this->invoiceService->createInvoice($validated);

            DB::commit();

            return new InvoiceResource($invoice->load(['customer', 'items', 'creator']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        return new InvoiceResource($invoice->load(['customer', 'items', 'payments', 'creator']));
    }

    public function update(InvoiceRequest $request, Invoice $invoice)
    {
        // Check if invoice can be edited
        if (!$invoice->canBeEdited()) {
            return response()->json([
                'error' => 'This invoice is locked and cannot be edited'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $invoice = $this->invoiceService->updateInvoice($invoice, $request->validated());

            DB::commit();

            return new InvoiceResource($invoice->load(['customer', 'items', 'creator']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Invoice $invoice)
    {
        Gate::authorize('delete', $invoice);

        if (!$invoice->canBeDeleted()) {
            return response()->json([
                'error' => 'Only draft invoices can be deleted'
            ], 403);
        }

        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully']);
    }

    public function send(Invoice $invoice)
    {
        Gate::authorize('send', $invoice);

        if ($invoice->isSent() || $invoice->isPaid()) {
            return response()->json([
                'error' => 'This invoice has already been sent or paid'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $this->invoiceService->sendInvoice($invoice);

            DB::commit();

            return response()->json(['message' => 'Invoice sent successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function markPaid(Invoice $invoice, Request $request)
    {
        Gate::authorize('markPaid', $invoice);

        if ($invoice->isPaid()) {
            return response()->json(['error' => 'Invoice is already paid'], 403);
        }

        DB::beginTransaction();
        try {
            $amount = $request->amount ?? $invoice->balance_due;
            $invoice = $this->invoiceService->markAsPaid($invoice, $amount);

            DB::commit();

            return response()->json([
                'message' => 'Invoice marked as paid',
                'invoice' => new InvoiceResource($invoice)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function duplicate(Invoice $invoice)
    {
        Gate::authorize('create', Invoice::class);

        DB::beginTransaction();
        try {
            $newInvoice = $this->invoiceService->duplicateInvoice($invoice);

            DB::commit();

            return new InvoiceResource($newInvoice->load(['customer', 'items', 'creator']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getStats(Request $request)
    {
        $stats = [
            'total' => Invoice::count(),
            'draft' => Invoice::where('status', 'draft')->count(),
            'sent' => Invoice::where('status', 'sent')->count(),
            'paid' => Invoice::where('status', 'paid')->count(),
            'overdue' => Invoice::where('status', 'overdue')->count(),
            'partially_paid' => Invoice::where('status', 'partially_paid')->count(),
            'total_amount' => Invoice::sum('total'),
            'paid_amount' => Invoice::where('status', 'paid')->sum('total'),
            'unpaid_amount' => Invoice::whereIn('status', ['sent', 'partially_paid', 'overdue'])->sum('balance_due'),
        ];

        return response()->json($stats);
    }

    public function download(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        // Generate PDF (implement later)
        return response()->json(['message' => 'PDF download coming soon']);
    }
}