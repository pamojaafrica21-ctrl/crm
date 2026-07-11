<?php

use App\Application\Sales\ConvertQuoteToInvoiceAction;
use App\Domain\Sales\Models\Quote;
use App\Domain\Shared\Enums\QuoteStatus;
use Livewire\Volt\Component;

new class extends Component
{
    public Quote $quote;

    public function mount(Quote $quote): void
    {
        $this->authorize('quotes.view');
        $this->quote = $quote->load(['customer', 'lines', 'creator', 'convertedInvoice']);
    }

    public function convertToInvoice(ConvertQuoteToInvoiceAction $action): void
    {
        $this->authorize('quotes.convert');
        $invoice = $action->execute($this->quote, auth()->id());
        $this->redirect(route('invoices.show', $invoice), navigate: true);
    }

    public function updateStatus(string $status): void
    {
        $this->authorize('quotes.update');
        $this->quote->update(['status' => QuoteStatus::from($status)]);
        $this->quote->refresh();
    }
}; ?>

<div class="max-w-4xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('quotes.index') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Quotes</a>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $quote->quote_number }}</h1>
            <p class="text-slate-500">Quote for {{ $quote->customer->fullName() }}</p>
        </div>
        <span class="inline-flex self-start px-3 py-1 bg-slate-100 rounded-full text-sm font-medium text-slate-700">
            {{ $quote->status->label() }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Issue date</div>
            <div class="mt-1 text-sm font-semibold text-slate-900">{{ $quote->issue_date?->format('M j, Y') ?? '—' }}</div>
            <p class="mt-1 text-xs text-slate-500">Date shown on the quote</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Valid until</div>
            <div class="mt-1 text-sm font-semibold text-slate-900">{{ $quote->valid_until?->format('M j, Y') ?? '—' }}</div>
            <p class="mt-1 text-xs text-slate-500">Last day the quote can be accepted</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Created by</div>
            <div class="mt-1 text-sm font-semibold text-slate-900">{{ $quote->creator?->name ?? '—' }}</div>
            <p class="mt-1 text-xs text-slate-500">Staff member who prepared this quote</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-6">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Line items</h2>
            <p class="text-xs text-slate-500 mt-0.5">Products and services included in this quote.</p>
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
                    @foreach ($quote->lines as $line)
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
                        <td class="pt-4 text-right text-sm font-medium">${{ number_format($quote->subtotal, 2) }}</td>
                    </tr>
                    @if ($quote->tax_amount > 0)
                        <tr>
                            <td colspan="3" class="pt-1 text-right text-sm text-slate-500">Tax</td>
                            <td class="pt-1 text-right text-sm font-medium">${{ number_format($quote->tax_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="3" class="pt-2 text-right font-semibold text-slate-900">Total ({{ $quote->currency }})</td>
                        <td class="pt-2 text-right font-bold text-lg text-slate-900">${{ number_format($quote->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($quote->notes || $quote->terms)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-100 pt-6">
                @if ($quote->notes)
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Notes</h3>
                        <p class="mt-1 text-sm text-slate-600 whitespace-pre-line">{{ $quote->notes }}</p>
                    </div>
                @endif
                @if ($quote->terms)
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Terms & conditions</h3>
                        <p class="mt-1 text-sm text-slate-600 whitespace-pre-line">{{ $quote->terms }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Workflow</h2>
            <p class="text-xs text-slate-500 mt-0.5">Update the quote status, then convert it to an invoice once the customer accepts.</p>
        </div>

        @can('quotes.update')
            <div>
                <div class="text-sm font-medium text-slate-700 mb-2">Update status</div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="updateStatus('sent')"
                            class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 {{ $quote->status === \App\Domain\Shared\Enums\QuoteStatus::Sent ? 'bg-slate-100 font-semibold' : '' }}">
                        Mark as sent
                    </button>
                    <button type="button" wire:click="updateStatus('accepted')"
                            class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 {{ $quote->status === \App\Domain\Shared\Enums\QuoteStatus::Accepted ? 'bg-green-50 border-green-200 text-green-800 font-semibold' : '' }}">
                        Mark as accepted
                    </button>
                    <button type="button" wire:click="updateStatus('rejected')"
                            class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 {{ $quote->status === \App\Domain\Shared\Enums\QuoteStatus::Rejected ? 'bg-red-50 border-red-200 text-red-800 font-semibold' : '' }}">
                        Mark as rejected
                    </button>
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    <span class="font-medium text-slate-600">Sent</span> means shared with the customer.
                    <span class="font-medium text-slate-600">Accepted</span> unlocks invoice conversion.
                    <span class="font-medium text-slate-600">Rejected</span> closes the quote.
                </p>
            </div>
        @endcan

        @can('quotes.convert')
            <div class="border-t border-slate-100 pt-4">
                <div class="text-sm font-medium text-slate-700 mb-1">Convert to invoice</div>
                @if ($quote->convertedInvoice)
                    <p class="text-sm text-slate-600">
                        This quote already has an invoice:
                        <a href="{{ route('invoices.show', $quote->convertedInvoice) }}" wire:navigate class="text-indigo-600 hover:text-indigo-700 font-medium">
                            {{ $quote->convertedInvoice->invoice_number }}
                        </a>
                    </p>
                @elseif ($quote->status === \App\Domain\Shared\Enums\QuoteStatus::Accepted)
                    <p class="text-xs text-slate-500 mb-3">Creates a new invoice with the same line items, notes, and terms. Due date is set to 30 days from today.</p>
                    <button type="button" wire:click="convertToInvoice" wire:confirm="Create an invoice from this quote?"
                            class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                        Convert to invoice
                    </button>
                @else
                    <p class="text-sm text-slate-500">Mark this quote as <span class="font-medium text-slate-700">Accepted</span> before converting it to an invoice.</p>
                @endif
            </div>
        @endcan
    </div>
</div>
