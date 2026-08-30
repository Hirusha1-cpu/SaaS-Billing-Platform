<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    InvoiceController,
    PaymentController,
    SubscriptionController,
    CustomerController,
    AIController,
    DashboardController,
    ReportController,
    AuditLogController,
    UserController,
    CompanyController
};

// Public Routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);

// Stripe Webhook
Route::post('/webhook/stripe', [PaymentController::class, 'processStripeWebhook'])
    ->name('stripe.webhook');

// Route::post('/api/webhook/stripe', [PaymentController::class, 'processStripeWebhook']);

// Protected Routes
Route::middleware(['auth:sanctum', 'multitenant'])->group(function () {

    // Auth Routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::put('/auth/change-password', [AuthController::class, 'changePassword']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/recent-invoices', [DashboardController::class, 'recentInvoices']);
    Route::get('/dashboard/activity', [DashboardController::class, 'activity']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    // Invoice Routes
    Route::apiResource('invoices', InvoiceController::class);
    Route::get('invoices/stats', [InvoiceController::class, 'getStats']);
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
    Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid']);
    Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate']);
    Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download']);

    // Payment Routes
    Route::apiResource('payments', PaymentController::class);
    Route::get('payments/stats', [PaymentController::class, 'getStats']);
    Route::post('payments/{payment}/refund', [PaymentController::class, 'refund']);
    Route::post('payments/create-stripe-session', [PaymentController::class, 'createStripeSession']);
    Route::post('payments/confirm-stripe', [PaymentController::class, 'confirmStripePayment']);

    // Subscription Routes
    Route::apiResource('subscriptions', SubscriptionController::class);
    Route::get('subscriptions/stats', [SubscriptionController::class, 'getStats']);
    Route::post('subscriptions/{subscription}/pause', [SubscriptionController::class, 'pause']);
    Route::post('subscriptions/{subscription}/resume', [SubscriptionController::class, 'resume']);
    Route::post('subscriptions/process-billing', [SubscriptionController::class, 'processBilling']);

    // Customer Routes
    Route::apiResource('customers', CustomerController::class);
    Route::get('customers/{customer}/stats', [CustomerController::class, 'getStats']);
    Route::get('customers/{customer}/invoices', [CustomerController::class, 'getInvoices']);
    Route::get('customers/{customer}/payments', [CustomerController::class, 'getPayments']);
    Route::post('customers/bulk-delete', [CustomerController::class, 'bulkDelete']);

    // AI Routes
    Route::post('ai/generate-invoice', [AIController::class, 'generateInvoice']);
    Route::post('ai/generate-reminder/{invoice}', [AIController::class, 'generateReminder']);
    Route::get('ai/insights', [AIController::class, 'getInsights']);
    Route::post('/ai/insights', [AIController::class, 'getInsights']); 
    Route::post('ai/parse-document', [AIController::class, 'parseDocument']);
    Route::post('ai/suggest-items', [AIController::class, 'suggestItems']);

    // Report Routes
    Route::get('reports/revenue', [ReportController::class, 'revenue']);
    Route::get('reports/invoice', [ReportController::class, 'invoice']);
    Route::get('reports/payment', [ReportController::class, 'payment']);
    Route::get('reports/export', [ReportController::class, 'export']);

    // Audit Log Routes
    Route::get('audit-logs', [AuditLogController::class, 'index']);
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show']);
    Route::get('audit-logs/model/{modelType}/{modelId}', [AuditLogController::class, 'forModel']);

    // User Management Routes
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
    Route::post('users/assign-role', [UserController::class, 'assignRole']);

    // Company Routes
    Route::get('company', [CompanyController::class, 'show']);
    Route::put('company', [CompanyController::class, 'update']);
    Route::get('company/settings', [CompanyController::class, 'settings']);
    Route::put('company/settings', [CompanyController::class, 'updateSettings']);
});
