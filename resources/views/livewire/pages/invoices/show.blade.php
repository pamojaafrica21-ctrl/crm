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
    public string $paymentDate = '';
    public string $paymentNotes = '';

    public function mount(Invoice $invoice): void
    {
        $this->authorize('invoices.view');
        $this->invoice = $invoice->load(['customer', 'lines', 'payments.recorder', 'quote']);
        $this->paymentDate = now()->toDateString();
        $this->paymentAmount = $invoice->outstandingBalance() > 0
            ? number_format($invoice->outstandingBalance(), 2, '.', '')
            : '';
    }

    public function recordPayment(): void
    {
        $this->authorize('payments.create');
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01|max:'.$this->invoice->outstandingBalance(),
            'paymentMethod' => 'required|in:cash,credit_card,bank_transfer',
            'paymentReference' => 'nullable|string|max:255',
            'paymentDate' => 'required|date',
            'paymentNotes' => 'nullable|string',
        ]);

        Payment::create([
            'property_id' => app(PropertyContext::class)->id(),
            'invoice_id' => $this->invoice->id,
            'recorded_by' => auth()->id(),
            'amount' => $this->paymentAmount,
            'currency' => $this->invoice->currency,
            'payment_method' => $this->paymentMethod,
            'reference' => $this->paymentReference ?: null,
            'payment_date' => $this->paymentDate,
            'notes' => $this->paymentNotes ?: null,
        ]);

        $this->paymentAmount = '';
        $this->paymentReference = '';
        $this->paymentNotes = '';
        $this->paymentDate = now()->toDateString();
        $this->invoice->refresh()->load(['customer', 'lines', 'payments.recorder', 'quote']);

        if ($this->invoice->outstandingBalance() > 0) {
            $this->paymentAmount = number_format($this->invoice->outstandingBalance(), 2, '.', '');
        }
    }
}; ?>

<div class="max-w-4xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('invoices.index') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Invoices</a>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $invoice->invoice_number }}</h1>
            <p class="text-slate-500">Invoice for {{ $invoice->customer->fullName() }}</p>
        </div>
        <span class="inline-flex self-start px-3 py-1 bg-slate-100 rounded-full text-sm font-medium text-slate-700">
            {{ $invoice->status->label() }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Issue date</div>
            <div class="mt-1 text-sm font-semibold text-slate-900">{{ $invoice->issue_date?->format('M j, Y') ?? '—' }}</div>
            <p class="mt-1 text-xs text-slate-500">When this invoice was issued</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Due date</div>
            <div class="mt-1 text-sm font-semibold text-slate-900">{{ $invoice->due_date?->format('M j, Y') ?? '—' }}</div>
            <p class="mt-1 text-xs text-slate-500">When payment is expected</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Source quote</div>
            <div class="mt-1 text-sm font-semibold text-slate-900">
                @if ($invoice->quote)
                    <a href="{{ route('quotes.show', $invoice->quote) }}" wire:navigate class="text-indigo-600 hover:text-indigo-700">
                        {{ $invoice->quote->quote_number }}
                    </a>
                @else
                    —
                @endif
            </div>
            <p class="mt-1 text-xs text-slate-500">Quote this invoice was created from</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-6">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Line items</h2>
            <p class="text-xs text-slate-500 mt-0.5">Charges included on this invoice.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-100">
                        <th class="py-2 font-medium">Description</th>
                        <th class="py-2 font-medium text-right">Quantity</th>
                        <th class="py-2 font-medium text-right">Unit price</th>
                        <th class="py-2 font-medium text-right">Line total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->lines as $line)
                        <tr class="border-b border-slate-50">
                            <td class="py-3 text-slate-900">{{ $line->description }}</td>
                            <td class="py-3 text-right text-slate-600">{{ number_format($line->quantity, 2) }}</td>
                            <td class="py-3 text-right text-slate-600">${{ number_format($line->unit_price, 2) }}</td>
                            <td class="py-3 text-right font-medium text-slate-900">${{ number_format($line->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="pt-4 text-right text-sm text-slate-500">Subtotal</td>
                        <td class="pt-4 text-right text-sm font-medium">${{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if ($invoice->tax_amount > 0)
                        <tr>
                            <td colspan="3" class="pt-1 text-right text-sm text-slate-500">Tax</td>
                            <td class="pt-1 text-right text-sm font-medium">${{ number_format($invoice->tax_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="3" class="pt-2 text-right font-semibold text-slate-900">Total ({{ $invoice->currency }})</td>
                        <td class="pt-2 text-right font-bold text-lg text-slate-900">${{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="pt-2 text-right text-sm text-slate-500">Amount paid</td>
                        <td class="pt-2 text-right text-sm font-medium text-green-600">${{ number_format($invoice->amount_paid, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="pt-1 text-right text-sm font-semibold text-slate-900">Outstanding balance</td>
                        <td class="pt-1 text-right text-sm font-bold text-orange-600">${{ number_format($invoice->outstandingBalance(), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($invoice->notes || $invoice->terms)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-100 pt-6">
                @if ($invoice->notes)
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Notes</h3>
                        <p class="mt-1 text-sm text-slate-600 whitespace-pre-line">{{ $invoice->notes }}</p>
                    </div>
                @endif
                @if ($invoice->terms)
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Terms & conditions</h3>
                        <p class="mt-1 text-sm text-slate-600 whitespace-pre-line">{{ $invoice->terms }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @can('payments.create')
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Record payment</h2>
                <p class="text-sm text-slate-500 mt-1">
                    Enter the amount received and how it was paid. The invoice status becomes
                    <span class="font-medium text-slate-700">Partially Paid</span> or
                    <span class="font-medium text-slate-700">Paid</span> automatically.
                </p>
            </div>

            @if ($invoice->total_amount <= 0)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    This invoice totals $0.00, so there is nothing to collect yet. Update the quote/invoice line prices, then convert or refresh totals before recording a payment.
                </div>
            @elseif ($invoice->outstandingBalance() <= 0)
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    This invoice is fully paid. New payments are not needed.
                </div>
            @else
                <form wire:submit="recordPayment" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Amount *</label>
                            <p class="text-xs text-slate-500 mb-1.5">Outstanding balance: ${{ number_format($invoice->outstandingBalance(), 2) }}</p>
                            <input type="number" wire:model="paymentAmount" step="0.01" min="0.01" max="{{ $invoice->outstandingBalance() }}"
                                   class="w-full rounded-lg border-slate-200 text-sm">
                            @error('paymentAmount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Payment date *</label>
                            <p class="text-xs text-slate-500 mb-1.5">When the payment was received.</p>
                            <input type="date" wire:model="paymentDate" class="w-full rounded-lg border-slate-200 text-sm">
                            @error('paymentDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Payment method *</label>
                        <p class="text-xs text-slate-500 mb-2">Choose how the customer paid.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="flex items-center gap-3 rounded-xl border px-4 py-3 cursor-pointer
                                          {{ $paymentMethod === 'cash' ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200 hover:bg-slate-50' }}">
                                <input type="radio" wire:model.live="paymentMethod" value="cash" class="text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-slate-800">Cash</span>
                            </label>
                            <label class="flex items-center gap-3 rounded-xl border px-4 py-3 cursor-pointer
                                          {{ $paymentMethod === 'credit_card' ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200 hover:bg-slate-50' }}">
                                <input type="radio" wire:model.live="paymentMethod" value="credit_card" class="text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-slate-800">Credit card</span>
                            </label>
                            <label class="flex items-center gap-3 rounded-xl border px-4 py-3 cursor-pointer
                                          {{ $paymentMethod === 'bank_transfer' ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200 hover:bg-slate-50' }}">
                                <input type="radio" wire:model.live="paymentMethod" value="bank_transfer" class="text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-slate-800">Bank transfer</span>
                            </label>
                        </div>
                        @error('paymentMethod') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Reference</label>
                            <p class="text-xs text-slate-500 mb-1.5">Check number, transaction ID, or receipt number.</p>
                            <input type="text" wire:model="paymentReference" class="w-full rounded-lg border-slate-200 text-sm" placeholder="Optional">
                            @error('paymentReference') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Payment notes</label>
                            <p class="text-xs text-slate-500 mb-1.5">Optional note for your records.</p>
                            <input type="text" wire:model="paymentNotes" class="w-full rounded-lg border-slate-200 text-sm" placeholder="Optional">
                            @error('paymentNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                        Record payment
                    </button>
                </form>
            @endif
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Record payment</h2>
            <p class="mt-1 text-sm text-slate-500">You do not have permission to record payments.</p>
        </div>
    @endcan

    @if ($invoice->payments->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-slate-900">Payment history</h2>
                <p class="text-xs text-slate-500 mt-0.5">All payments recorded against this invoice.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b border-slate-100">
                            <th class="py-2 font-medium">Date</th>
                            <th class="py-2 font-medium">Method</th>
                            <th class="py-2 font-medium">Reference</th>
                            <th class="py-2 font-medium">Recorded by</th>
                            <th class="py-2 font-medium text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($invoice->payments as $payment)
                            <tr>
                                <td class="py-3 text-slate-900">{{ $payment->payment_date->format('M j, Y') }}</td>
                                <td class="py-3 text-slate-600">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td class="py-3 text-slate-600">
                                    {{ $payment->reference ?: '—' }}
                                    @if ($payment->notes)
                                        <div class="text-xs text-slate-400 mt-0.5">{{ $payment->notes }}</div>
                                    @endif
                                </td>
                                <td class="py-3 text-slate-600">{{ $payment->recorder?->name ?? '—' }}</td>
                                <td class="py-3 text-right font-medium text-slate-900">${{ number_format($payment->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
