<?php

use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'pages.home')->name('home');

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/llms.txt', [SeoController::class, 'llms'])->name('llms');
Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('dashboard', 'pages.dashboard.index')->name('dashboard');

    Volt::route('customers', 'pages.customers.index')->name('customers.index');
    Volt::route('customers/segments', 'pages.customers.segments')->name('customers.segments');
    Volt::route('customers/create', 'pages.customers.create')->name('customers.create');
    Volt::route('customers/{customer}', 'pages.customers.show')->name('customers.show');

    Volt::route('quotes', 'pages.quotes.index')->name('quotes.index');
    Volt::route('quotes/create', 'pages.quotes.create')->name('quotes.create');
    Volt::route('quotes/{quote}', 'pages.quotes.show')->name('quotes.show');

    Volt::route('invoices', 'pages.invoices.index')->name('invoices.index');
    Volt::route('invoices/{invoice}', 'pages.invoices.show')->name('invoices.show');

    Volt::route('tasks', 'pages.tasks.index')->name('tasks.index');
    Volt::route('tasks/create', 'pages.tasks.create')->name('tasks.create');
    Volt::route('tasks/{task}', 'pages.tasks.show')->name('tasks.show');

    Volt::route('appointments', 'pages.appointments.index')->name('appointments.index');
    Volt::route('appointments/create', 'pages.appointments.create')->name('appointments.create');

    Volt::route('targets', 'pages.targets.index')->name('targets.index');
    Volt::route('targets/create', 'pages.targets.create')->name('targets.create');

    Volt::route('staff', 'pages.staff.index')->name('staff.index');
    Volt::route('reports', 'pages.reports.index')->name('reports.index');
    Volt::route('sync', 'pages.sync.index')->name('sync.index');
    Volt::route('announcements', 'pages.announcements.index')->name('announcements.index');

    Volt::route('billing', 'pages.billing.index')->name('billing');

    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
