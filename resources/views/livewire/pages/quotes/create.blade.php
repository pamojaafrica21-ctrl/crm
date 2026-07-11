<?php

use App\Domain\Customers\Models\Customer;
use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Sales\Models\Quote;
use App\Domain\Sales\Models\QuoteLine;
use Livewire\Volt\Component;

new class extends Component
{
    public string $customer_id = '';
    public string $issue_date = '';
    public string $valid_until = '';
    public string $notes = '';
    public string $terms = '';
    public array $lines = [['description' => '', 'quantity' => 1, 'unit_price' => 0]];

    public function mount(): void
    {
        $this->authorize('quotes.create');
        $this->issue_date = now()->toDateString();
        $this->valid_until = now()->addDays(30)->toDateString();
    }

    public function customers()
    {
        return Customer::orderBy('last_name')->get();
    }

    public function lineSubtotal(): float
    {
        return collect($this->lines)->sum(fn ($line) => (float) ($line['quantity'] ?? 0) * (float) ($line['unit_price'] ?? 0));
    }

    public function addLine(): void
    {
        $this->lines[] = ['description' => '', 'quantity' => 1, 'unit_price' => 0];
    }

    public function removeLine(int $index): void
    {
        if (count($this->lines) <= 1) {
            return;
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function save(): void
    {
        $this->authorize('quotes.create');
        $this->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string|max:255',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
        ]);

        $propertyId = app(PropertyContext::class)->id();
        $count = Quote::withoutGlobalScope('property')->where('property_id', $propertyId)->count() + 1;

        $quote = Quote::create([
            'property_id' => $propertyId,
            'customer_id' => $this->customer_id,
            'created_by' => auth()->id(),
            'quote_number' => sprintf('QT-%04d-%05d', $propertyId, $count),
            'issue_date' => $this->issue_date,
            'valid_until' => $this->valid_until ?: null,
            'notes' => $this->notes ?: null,
            'terms' => $this->terms ?: null,
            'currency' => app(PropertyContext::class)->property()?->currency ?? 'USD',
        ]);

        $subtotal = 0;
        foreach ($this->lines as $i => $line) {
            $lineTotal = (float) $line['quantity'] * (float) $line['unit_price'];
            QuoteLine::create([
                'quote_id' => $quote->id,
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'line_total' => $lineTotal,
                'sort_order' => $i,
            ]);
            $subtotal += $lineTotal;
        }

        $quote->update(['subtotal' => $subtotal, 'total_amount' => $subtotal]);

        $this->redirect(route('quotes.show', $quote), navigate: true);
    }
}; ?>

<div class="max-w-4xl space-y-6">
    <div>
        <a href="{{ route('quotes.index') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Quotes</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">New Quote</h1>
        <p class="mt-1 text-sm text-slate-500">Create a price proposal for a customer. Accepted quotes can later be converted into invoices.</p>
    </div>

    <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-8">
        <section class="space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Quote details</h2>
                <p class="text-xs text-slate-500 mt-0.5">Who the quote is for and how long it remains valid.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Customer *</label>
                <p class="text-xs text-slate-500 mb-1.5">The guest or company receiving this quote.</p>
                <select wire:model="customer_id" class="w-full rounded-lg border-slate-200">
                    <option value="">Select customer…</option>
                    @foreach ($this->customers() as $c)
                        <option value="{{ $c->id }}">{{ $c->fullName() }}@if($c->company) — {{ $c->company }}@endif</option>
                    @endforeach
                </select>
                @error('customer_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Issue date *</label>
                    <p class="text-xs text-slate-500 mb-1.5">The date shown on the quote document.</p>
                    <input type="date" wire:model="issue_date" class="w-full rounded-lg border-slate-200">
                    @error('issue_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Valid until</label>
                    <p class="text-xs text-slate-500 mb-1.5">Last day the customer can accept this quote.</p>
                    <input type="date" wire:model="valid_until" class="w-full rounded-lg border-slate-200">
                    @error('valid_until') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Line items *</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Each row is a product or service included in the quote.</p>
                </div>
                <button type="button" wire:click="addLine" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 shrink-0">
                    + Add line
                </button>
            </div>

            <div class="hidden md:grid md:grid-cols-12 gap-2 text-xs font-medium text-slate-500 px-1">
                <div class="col-span-6">Description</div>
                <div class="col-span-2">Quantity</div>
                <div class="col-span-3">Unit price</div>
                <div class="col-span-1"></div>
            </div>

            @foreach ($lines as $i => $line)
                <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4 space-y-3 md:space-y-0 md:grid md:grid-cols-12 md:gap-2 md:items-start md:bg-transparent md:border-0 md:p-0">
                    <div class="md:col-span-6">
                        <label class="block text-xs font-medium text-slate-600 mb-1 md:sr-only">Description *</label>
                        <p class="text-[11px] text-slate-400 mb-1 md:hidden">What is being quoted</p>
                        <input type="text" wire:model="lines.{{ $i }}.description" placeholder="e.g. Deluxe room — 2 nights"
                               class="w-full rounded-lg border-slate-200 text-sm">
                        @error("lines.$i.description") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1 md:sr-only">Quantity *</label>
                        <p class="text-[11px] text-slate-400 mb-1 md:hidden">Number of units</p>
                        <input type="number" wire:model.live="lines.{{ $i }}.quantity" step="0.01" min="0.01"
                               class="w-full rounded-lg border-slate-200 text-sm">
                        @error("lines.$i.quantity") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-slate-600 mb-1 md:sr-only">Unit price *</label>
                        <p class="text-[11px] text-slate-400 mb-1 md:hidden">Price per unit (before tax)</p>
                        <input type="number" wire:model.live="lines.{{ $i }}.unit_price" step="0.01" min="0"
                               class="w-full rounded-lg border-slate-200 text-sm">
                        @error("lines.$i.unit_price") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-1 flex md:justify-end md:pt-0.5">
                        @if (count($lines) > 1)
                            <button type="button" wire:click="removeLine({{ $i }})"
                                    class="text-sm text-red-500 hover:text-red-700">
                                Remove
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end border-t border-slate-100 pt-4">
                <div class="text-right">
                    <div class="text-xs text-slate-500">Estimated total</div>
                    <div class="text-lg font-bold text-slate-900">${{ number_format($this->lineSubtotal(), 2) }}</div>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Notes & terms</h2>
                <p class="text-xs text-slate-500 mt-0.5">Optional details shown with the quote.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <p class="text-xs text-slate-500 mb-1.5">Extra context for the customer or your team (special requests, inclusions, etc.).</p>
                <textarea wire:model="notes" rows="3" class="w-full rounded-lg border-slate-200 text-sm"
                          placeholder="e.g. Includes breakfast and late checkout"></textarea>
                @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Terms & conditions</label>
                <p class="text-xs text-slate-500 mb-1.5">Payment, cancellation, or acceptance terms for this quote.</p>
                <textarea wire:model="terms" rows="3" class="w-full rounded-lg border-slate-200 text-sm"
                          placeholder="e.g. 50% deposit required to confirm"></textarea>
                @error('terms') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                Create quote
            </button>
            <a href="{{ route('quotes.index') }}" wire:navigate class="px-4 py-2 border border-slate-200 text-sm font-medium rounded-lg hover:bg-slate-50">
                Cancel
            </a>
        </div>
    </form>
</div>
