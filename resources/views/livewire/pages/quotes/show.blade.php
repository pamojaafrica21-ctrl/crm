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

<div class="max-w-3xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('quotes.index') }}" wire:navigate class="text-sm text-indigo-600">← Quotes</a>
                <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $quote->quote_number }}</h1>
                <p class="text-slate-500">{{ $quote->customer->fullName() }}</p>
            </div>
            <span class="px-3 py-1 bg-slate-100 rounded-full text-sm">{{ $quote->status->label() }}</span>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <table class="w-full text-sm mb-4">
                <thead><tr class="text-slate-500"><th class="text-left py-2">Description</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                    @foreach ($quote->lines as $line)
                        <tr class="border-t border-slate-50">
                            <td class="py-2">{{ $line->description }}</td>
                            <td class="text-right">{{ $line->quantity }}</td>
                            <td class="text-right">${{ number_format($line->unit_price, 2) }}</td>
                            <td class="text-right font-medium">${{ number_format($line->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr class="border-t border-slate-200"><td colspan="3" class="py-3 text-right font-semibold">Total</td><td class="text-right font-bold text-lg">${{ number_format($quote->total_amount, 2) }}</td></tr></tfoot>
            </table>

            <div class="flex gap-2 flex-wrap">
                @can('quotes.update')
                    @foreach (['sent', 'accepted', 'rejected'] as $status)
                        <button wire:click="updateStatus('{{ $status }}')" class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 capitalize">{{ $status }}</button>
                    @endforeach
                @endcan
                @can('quotes.convert')
                    @if ($quote->status === \App\Domain\Shared\Enums\QuoteStatus::Accepted && ! $quote->convertedInvoice)
                        <button wire:click="convertToInvoice" class="px-4 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">Convert to Invoice</button>
                    @endif
                @endcan
            </div>
        </div>
    </div>
