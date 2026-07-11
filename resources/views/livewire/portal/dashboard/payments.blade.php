<?php

use App\Domain\Sales\Models\Payment;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public function with(): array
    {
        $invoiceIds = auth('guest')->user()->invoices()->pluck('id');

        return [
            'payments' => Payment::query()
                ->whereIn('invoice_id', $invoiceIds)
                ->with('invoice')
                ->latest('payment_date')
                ->get(),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-4xl text-stone-900">{{ __('portal.dashboard.payments') }}</h1>
        <div class="mt-8 space-y-4">
            @forelse($payments as $payment)
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="font-semibold text-stone-900">{{ $payment->invoice?->invoice_number }}</div>
                        <div class="text-sm text-stone-500">{{ $payment->payment_date->format('M j, Y') }} · {{ $payment->payment_method }}</div>
                        @if($payment->reference)<div class="text-xs text-stone-400">{{ $payment->reference }}</div>@endif
                    </div>
                    <div class="font-semibold text-stone-900">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</div>
                </div>
            @empty
                <p class="text-stone-500">{{ __('portal.dashboard.empty') }}</p>
            @endforelse
        </div>
    </div>
</div>
