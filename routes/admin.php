<?php

use App\Http\Controllers\MpesaCallbackController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::post('mpesa/callback', MpesaCallbackController::class)->name('mpesa.callback');
Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

Route::prefix('admin')->middleware(['auth', 'verified', 'super-admin'])->name('admin.')->group(function () {
    Volt::route('/', 'pages.admin.dashboard')->name('dashboard');
    Volt::route('analytics', 'pages.admin.analytics.index')->name('analytics');
    Volt::route('organizations', 'pages.admin.organizations.index')->name('organizations.index');
    Volt::route('organizations/create', 'pages.admin.organizations.create')->name('organizations.create');
    Volt::route('organizations/{organization}/edit', 'pages.admin.organizations.edit')->name('organizations.edit');
    Volt::route('organizations/{organization}', 'pages.admin.organizations.show')->name('organizations.show');
    Volt::route('plans', 'pages.admin.plans.index')->name('plans.index');
    Volt::route('homepage', 'pages.admin.homepage.index')->name('homepage.index');
    Volt::route('payments', 'pages.admin.payments.index')->name('payments.index');
    Volt::route('settings', 'pages.admin.settings.index')->name('settings.index');
});
