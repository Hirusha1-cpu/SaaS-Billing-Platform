<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;

// Payment routes (MUST be before catch-all)
Route::get('/payment/success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/cancel', [PaymentController::class, 'paymentCancel'])->name('payment.cancel');

// Catch all routes - React SPA (MUST be last)
Route::view('/{any?}', 'app')->where('any', '.*');