<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['portal.property', 'portal.locale'])->group(function () {
    Volt::route('/', 'portal.home')->name('portal.home');

    Volt::route('/rooms', 'portal.rooms.index')->name('portal.rooms.index');
    Volt::route('/rooms/{roomType}', 'portal.rooms.show')->name('portal.rooms.show');
    Volt::route('/booking', 'portal.booking.create')->name('portal.booking.create');

    Volt::route('/restaurant', 'portal.restaurant.menu')->name('portal.restaurant.menu');
    Volt::route('/restaurant/cart', 'portal.restaurant.cart')->name('portal.restaurant.cart');
    Volt::route('/restaurant/reserve', 'portal.restaurant.reserve')->name('portal.restaurant.reserve');

    Volt::route('/promotions', 'portal.promotions.index')->name('portal.promotions.index');
    Volt::route('/faq', 'portal.faq.index')->name('portal.faq.index');
    Volt::route('/contact', 'portal.contact.index')->name('portal.contact.index');

    Route::middleware('guest:guest')->prefix('guest')->group(function () {
        Volt::route('/login', 'portal.auth.login')->name('guest.login');
        Volt::route('/register', 'portal.auth.register')->name('guest.register');
        Volt::route('/forgot-password', 'portal.auth.forgot-password')->name('guest.password.request');
        Volt::route('/reset-password/{token}', 'portal.auth.reset-password')->name('guest.password.reset');
    });

    Route::middleware('auth:guest')->prefix('account')->group(function () {
        Volt::route('/', 'portal.dashboard.index')->name('portal.dashboard');
        Volt::route('/profile', 'portal.dashboard.profile')->name('portal.dashboard.profile');
        Volt::route('/bookings', 'portal.dashboard.bookings')->name('portal.dashboard.bookings');
        Volt::route('/bookings/{groupCode}', 'portal.dashboard.booking-show')->name('portal.dashboard.booking-show');
        Volt::route('/orders', 'portal.dashboard.orders')->name('portal.dashboard.orders');
        Volt::route('/invoices', 'portal.dashboard.invoices')->name('portal.dashboard.invoices');
        Volt::route('/payments', 'portal.dashboard.payments')->name('portal.dashboard.payments');
        Volt::route('/quotes', 'portal.dashboard.quotes')->name('portal.dashboard.quotes');
        Volt::route('/reservations', 'portal.dashboard.reservations')->name('portal.dashboard.reservations');
        Volt::route('/favorites', 'portal.dashboard.favorites')->name('portal.dashboard.favorites');
        Volt::route('/notifications', 'portal.dashboard.notifications')->name('portal.dashboard.notifications');
        Volt::route('/reviews/create', 'portal.reviews.create')->name('portal.reviews.create');

        Route::post('/logout', function () {
            Auth::guard('guest')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('portal.home');
        })->name('guest.logout');
    });
});
