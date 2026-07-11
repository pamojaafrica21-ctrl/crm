<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public function with(): array
    {
        return [
            'orders' => auth('guest')->user()->fnbOrders()->with('items')->latest('ordered_at')->get(),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-4xl text-stone-900">{{ __('portal.dashboard.orders') }}</h1>
        <div class="mt-8 space-y-4">
            @forelse($orders as $order)
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="font-semibold text-stone-900">{{ $order->order_number }}</div>
                            <div class="text-sm text-stone-500">{{ $order->ordered_at?->format('M j, Y g:i A') }} · {{ str_replace('_', ' ', $order->order_type ?? 'dine_in') }}</div>
                        </div>
                        <div class="text-right">
                            <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold uppercase">{{ $order->status }}</span>
                            <div class="mt-2 font-semibold">{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</div>
                        </div>
                    </div>
                    <ul class="mt-4 space-y-1 text-sm text-stone-600">
                        @foreach($order->items as $item)
                            <li>{{ $item->name }} × {{ $item->quantity }}</li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="text-stone-500">{{ __('portal.dashboard.empty') }}</p>
            @endforelse
        </div>
    </div>
</div>
