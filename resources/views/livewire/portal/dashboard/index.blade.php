<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public function with(): array
    {
        $customer = auth('guest')->user();

        return [
            'customer' => $customer,
            'loyalty' => $customer->loyaltyAccount,
            'upcoming' => $customer->reservations()
                ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                ->where('check_out', '>=', now()->toDateString())
                ->orderBy('check_in')
                ->limit(5)
                ->get(),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-[2rem] bg-stone-950 px-8 py-10 text-white shadow-xl">
            <p class="text-sm uppercase tracking-[0.25em] text-amber-200/80">{{ __('portal.dashboard.title') }}</p>
            <h1 class="mt-3 font-display text-4xl sm:text-5xl">{{ $customer->fullName() }}</h1>
            <p class="mt-2 text-stone-300">{{ $customer->email }}</p>
            @if($loyalty)
                <p class="mt-4 text-sm text-amber-200/90">{{ __('portal.dashboard.loyalty') }}: {{ number_format($loyalty->points) }} · {{ ucfirst($loyalty->tier) }}</p>
            @endif
        </div>

        <div class="mt-10 grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="font-display text-2xl text-stone-900">{{ __('portal.dashboard.upcoming') }}</h2>
                    @forelse($upcoming as $booking)
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 pt-4">
                            <div>
                                <div class="font-semibold text-stone-900">{{ $booking->room_type ?: 'Room' }}</div>
                                <div class="text-sm text-stone-500">{{ $booking->check_in->format('M j') }} – {{ $booking->check_out->format('M j, Y') }}</div>
                            </div>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-800">{{ __('portal.status.'.$booking->status, [], $booking->status) }}</span>
                        </div>
                    @empty
                        <p class="mt-4 text-sm text-stone-500">{{ __('portal.dashboard.empty') }}</p>
                        @if(Route::has('portal.rooms.index'))
                            <a href="{{ route('portal.rooms.index') }}" wire:navigate class="portal-btn mt-6">{{ __('portal.nav.book_now') }}</a>
                        @endif
                    @endforelse
                </section>
            </div>

            <aside class="space-y-3">
                @foreach([
                    ['route' => 'portal.dashboard.bookings', 'label' => __('portal.dashboard.upcoming')],
                    ['route' => 'portal.dashboard.orders', 'label' => __('portal.dashboard.orders')],
                    ['route' => 'portal.dashboard.reservations', 'label' => __('portal.dashboard.reservations')],
                    ['route' => 'portal.dashboard.invoices', 'label' => __('portal.dashboard.invoices')],
                    ['route' => 'portal.dashboard.payments', 'label' => __('portal.dashboard.payments')],
                    ['route' => 'portal.dashboard.quotes', 'label' => __('portal.dashboard.quotes')],
                    ['route' => 'portal.dashboard.favorites', 'label' => __('portal.dashboard.favorites')],
                    ['route' => 'portal.dashboard.notifications', 'label' => 'Notifications'],
                    ['route' => 'portal.dashboard.profile', 'label' => __('portal.dashboard.profile')],
                ] as $link)
                    @if(Route::has($link['route']))
                        <a href="{{ route($link['route']) }}" wire:navigate class="block rounded-2xl border border-stone-200 bg-white px-5 py-4 text-sm font-semibold text-stone-800 shadow-sm transition hover:border-amber-300">
                            {{ $link['label'] }}
                        </a>
                    @endif
                @endforeach
            </aside>
        </div>
    </div>
</div>
