<?php

use App\Domain\Restaurant\Models\TableReservation;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public function with(): array
    {
        return [
            'reservations' => TableReservation::query()
                ->with('table')
                ->where('customer_id', auth('guest')->id())
                ->latest('reserved_at')
                ->get(),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4">
            <h1 class="font-display text-4xl text-stone-900">{{ __('portal.dashboard.reservations') }}</h1>
            <a href="{{ route('portal.restaurant.reserve') }}" wire:navigate class="portal-btn">{{ __('portal.restaurant.table_reserve') }}</a>
        </div>
        <div class="mt-8 space-y-4">
            @forelse($reservations as $reservation)
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="font-semibold text-stone-900">{{ $reservation->confirmation_number }}</div>
                        <div class="text-sm text-stone-500">{{ $reservation->reserved_at->format('M j, Y g:i A') }} · {{ $reservation->party_size }} guests</div>
                        @if($reservation->table)<div class="text-sm text-stone-600">Table {{ $reservation->table->name }}</div>@endif
                    </div>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase text-amber-800">{{ $reservation->status }}</span>
                </div>
            @empty
                <p class="text-stone-500">{{ __('portal.dashboard.empty') }}</p>
            @endforelse
        </div>
    </div>
</div>
