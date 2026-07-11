<?php

use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Sales\Models\Quote;
use App\Domain\Shared\Enums\QuoteStatus;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public function with(): array
    {
        return [
            'quotes' => Quote::with('customer', 'creator')->latest()->paginate(15),
        ];
    }
}; ?>

<div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">Quotes</h1>
            @can('quotes.create')
                <a href="{{ route('quotes.create') }}" wire:navigate class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">New Quote</a>
            @endcan
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Number</th>
                        <th class="text-left px-4 py-3">Customer</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Issue Date</th>
                        <th class="text-right px-4 py-3">Total</th>
                        <th class="text-right px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($quotes as $quote)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium">{{ $quote->quote_number }}</td>
                            <td class="px-4 py-3">{{ $quote->customer->fullName() }}</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 bg-slate-100 rounded-full text-xs">{{ $quote->status->label() }}</span></td>
                            <td class="px-4 py-3">{{ $quote->issue_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-right font-medium">${{ number_format($quote->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('quotes.show', $quote) }}" wire:navigate class="text-indigo-600">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No quotes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $quotes->links() }}</div>
        </div>
    </div>
