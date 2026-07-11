<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public function with(): array
    {
        return [
            'quotes' => auth('guest')->user()->quotes()->latest()->get(),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-4xl text-stone-900">{{ __('portal.dashboard.quotes') }}</h1>
        <div class="mt-8 space-y-4">
            @forelse($quotes as $quote)
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="font-semibold text-stone-900">{{ $quote->quote_number }}</div>
                        <div class="text-sm text-stone-500">{{ $quote->created_at?->format('M j, Y') }}</div>
                    </div>
                    <div class="text-right">
                        <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold uppercase">{{ $quote->status->value ?? $quote->status }}</span>
                        <div class="mt-2 font-semibold">{{ $quote->currency }} {{ number_format($quote->total_amount, 2) }}</div>
                    </div>
                </div>
            @empty
                <p class="text-stone-500">{{ __('portal.dashboard.empty') }}</p>
            @endforelse
        </div>
    </div>
</div>
