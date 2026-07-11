<?php

use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\Payment;
use Livewire\Volt\Component;

new class extends Component
{
    public Invoice $invoice;
    public string $paymentAmount = '';
    public string $paymentMethod = 'cash';
    public string $paymentReference = '';

    public function mount(Invoice $invoice): void
    {
        $this->authorize('invoices.view');
        $this->invoice = $invoice->load(['customer', 'lines', 'payments.recorder']);
    }

    public function recordPayment(): void
    {
        $this->authorize('payments.create');
        $this->validate(['paymentAmount' => 'required|numeric|min:0.01']);

        Payment::create([
            'property_id' => app(PropertyContext::class)->id(),
            'invoice_id' => $this->invoice->id,
            'recorded_by' => auth()->id(),
            'amount' => $this->paymentAmount,
            'currency' => $this->invoice->currency,
            'payment_method' => $this->paymentMethod,
            'reference' => $this->paymentReference,
            'payment_date' => now()->toDateString(),
        ]);

        $this->paymentAmount = '';
        $this->paymentReference = '';
        $this->invoice->refresh()->load('payments.recorder');
    }
}; ?>

<div class="max-w-3xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('invoices.index') }}" wire:navigate class="text-sm text-indigo-600">← Invoices</a>
                <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $invoice->invoice_number }}</h1>
                <p class="text-slate-500">{{ $invoice->customer->fullName() }}</p>
            </div>
            <span class="px-3 py-1 bg-slate-100 rounded-full text-sm">{{ $invoice->status->label() }}</span>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <table class="w-full text-sm mb-4">
                @foreach ($invoice->lines as $line)
                    <tr class="border-b border-slate-50">
                        <td class="py-2">{{ $line->description }}</td>
                        <td class="text-right font-medium">${{ number_format($line->line_total, 2) }}</td>
                    </tr>
                @endforeach
                <tr><td class="py-3 font-semibold">Total</td><td class="text-right font-bold">${{ number_format($invoice->total_amount, 2) }}</td></tr>
                <tr><td class="py-1 text-slate-500">Paid</td><td class="text-right text-green-600">${{ number_format($invoice->amount_paid, 2) }}</td></tr>
                <tr><td class="py-1 font-medium">Outstanding</td><td class="text-right text-orange-600 font-bold">${{ number_format($invoice->outstandingBalance(), 2) }}</td></tr>
            </table>
        </div>

        @can('payments.create')
            @if ($invoice->outstandingBalance() > 0)
                <form wire:submit="recordPayment" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-3">
                    <h2 class="font-semibold">Record Payment</h2>
                    <div class="grid grid-cols-3 gap-3">
                        <input type="number" wire:model="paymentAmount" step="0.01" placeholder="Amount" class="rounded-lg border-slate-200 text-sm">
                        <select wire:model="paymentMethod" class="rounded-lg border-slate-200 text-sm">
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                        <input type="text" wire:model="paymentReference" placeholder="Reference" class="rounded-lg border-slate-200 text-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Record Payment</button>
                </form>
            @endif
        @endcan

        @if ($invoice->payments->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="font-semibold mb-3">Payment History</h2>
                @foreach ($invoice->payments as $payment)
                    <div class="flex justify-between py-2 border-b border-slate-50 text-sm">
                        <span>{{ $payment->payment_date->format('M j, Y') }} — {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                        <span class="font-medium">${{ number_format($payment->amount, 2) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
