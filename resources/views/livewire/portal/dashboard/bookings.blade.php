<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public function with(): array
    {
        $customer = auth('guest')->user();

        return [
            'bookings' => $customer->reservations()
                ->with('roomType')
                ->latest('check_in')
                ->get()
                ->groupBy(fn ($r) => $r->group_code ?: $r->confirmation_number),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4">
            <h1 class="font-display text-4xl text-stone-900">{{ __('portal.dashboard.upcoming') }}</h1>
            <a href="{{ route('portal.dashboard') }}" wire:navigate class="text-sm font-semibold text-amber-800">← {{ __('portal.dashboard.title') }}</a>
        </div>

        <div class="mt-8 space-y-4">
            @forelse($bookings as $code => $group)
                @php $first = $group->first(); @endphp
                <a href="{{ route('portal.dashboard.booking-show', $code) }}" wire:navigate class="block rounded-3xl border border-stone-200 bg-white p-6 shadow-sm transition hover:border-amber-300">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="font-semibold text-stone-900">{{ $code }}</div>
                            <div class="text-sm text-stone-500">{{ $first->check_in->format('M j') }} – {{ $first->check_out->format('M j, Y') }} · {{ $group->count() }} room(s)</div>
                            <div class="mt-1 text-sm text-stone-600">{{ $group->pluck('room_type')->filter()->unique()->implode(', ') }}</div>
                        </div>
                        <div class="text-right">
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase text-amber-800">{{ __('portal.status.'.$first->status->value) }}</span>
                            <div class="mt-2 font-semibold text-stone-900">{{ $first->currency }} {{ number_format($group->sum('total_amount'), 2) }}</div>
                        </div>
                    </div>
                </a>
            @empty
                <p class="rounded-3xl border border-dashed border-stone-300 bg-white px-8 py-16 text-center text-stone-500">{{ __('portal.dashboard.empty') }}</p>
            @endforelse
        </div>
    </div>
</div>
