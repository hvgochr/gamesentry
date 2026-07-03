<?php

use App\Http\Controllers\Billing\BillingController;
use App\Http\Controllers\Billing\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\PaymentController;

Route::middleware(['auth', 'verified'])
    ->prefix('dashboard/billing')
    ->name('dashboard.billing.')
    ->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('index');
        Route::post('checkout', [BillingController::class, 'checkout'])->name('checkout');
        Route::post('portal', [BillingController::class, 'portal'])->name('portal');
    });

Route::prefix(config('cashier.path', 'stripe'))
    ->name('cashier.')
    ->group(function () {
        Route::get('payment/{id}', [PaymentController::class, 'show'])->name('payment');
        Route::post('webhook', [StripeWebhookController::class, 'handleWebhook'])->name('webhook');
    });
