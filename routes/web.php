<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'property', 'property.access'])->group(function () {
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
    Volt::route('sync', 'pages.sync.index')->name('sync.index');
    Volt::route('announcements', 'pages.announcements.index')->name('announcements.index');

    Volt::route('manage/rooms', 'pages.rooms.index')->name('rooms.index');
    Volt::route('manage/rooms/create', 'pages.rooms.create')->name('rooms.create');
    Volt::route('manage/rooms/extras', 'pages.rooms.extras')->name('rooms.extras');
    Volt::route('manage/rooms/{roomType}', 'pages.rooms.show')->name('rooms.show');

    Volt::route('manage/reservations', 'pages.reservations.index')->name('reservations.index');

    Volt::route('manage/menu', 'pages.menu.index')->name('menu.index');
    Volt::route('manage/menu/items', 'pages.menu.items')->name('menu.items');

    Volt::route('manage/content/promotions', 'pages.content.promotions')->name('content.promotions');
    Volt::route('manage/content/coupons', 'pages.content.coupons')->name('content.coupons');
    Volt::route('manage/content/faqs', 'pages.content.faqs')->name('content.faqs');
    Volt::route('manage/content/inquiries', 'pages.content.inquiries')->name('content.inquiries');
    Volt::route('manage/content/reviews', 'pages.content.reviews')->name('content.reviews');

    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
