<?php

use App\Domain\Sales\Models\Invoice;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public function with(): array
    {
        return ['invoices' => Invoice::with('customer', 'quote')->latest()->paginate(15)];
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Invoices</h1>
        <p class="mt-1 text-sm text-slate-500">Bills created from accepted quotes. Open an invoice to review charges and record payments.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">Invoice number</th>
                    <th class="text-left px-4 py-3 font-medium">Customer</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                    <th class="text-left px-4 py-3 font-medium">Issue date</th>
                    <th class="text-left px-4 py-3 font-medium">Due date</th>
                    <th class="text-right px-4 py-3 font-medium">Total</th>
                    <th class="text-right px-4 py-3 font-medium">Outstanding</th>
                    <th class="text-right px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($invoices as $invoice)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $invoice->invoice_number }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $invoice->customer->fullName() }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 bg-slate-100 rounded-full text-xs text-slate-700">{{ $invoice->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $invoice->issue_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $invoice->due_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-slate-900">${{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-orange-600">${{ number_format($invoice->outstandingBalance(), 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('invoices.show', $invoice) }}" wire:navigate class="text-indigo-600 hover:text-indigo-700">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">No invoices yet. Accept a quote and convert it to create one.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $invoices->links() }}</div>
    </div>
</div>
