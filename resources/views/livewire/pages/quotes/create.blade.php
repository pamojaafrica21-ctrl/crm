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
    public array $lines = [['description' => '', 'quantity' => 1, 'unit_price' => 0]];

    public function mount(): void
    {
        $this->issue_date = now()->toDateString();
        $this->valid_until = now()->addDays(30)->toDateString();
    }

    public function customers()
    {
        return Customer::orderBy('last_name')->get();
    }

    public function addLine(): void
    {
        $this->lines[] = ['description' => '', 'quantity' => 1, 'unit_price' => 0];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function save(): void
    {
        $this->authorize('quotes.create');
        $this->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
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
            'valid_until' => $this->valid_until,
            'notes' => $this->notes,
            'currency' => app(PropertyContext::class)->property()?->currency ?? 'USD',
        ]);

        $subtotal = 0;
        foreach ($this->lines as $i => $line) {
            $lineTotal = $line['quantity'] * $line['unit_price'];
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

<div class="max-w-3xl space-y-6">
        <h1 class="text-2xl font-bold text-slate-900">New Quote</h1>
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Customer *</label>
                <select wire:model="customer_id" class="w-full rounded-lg border-slate-200">
                    <option value="">Select customer...</option>
                    @foreach ($this->customers() as $c)
                        <option value="{{ $c->id }}">{{ $c->fullName() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Issue Date</label>
                    <input type="date" wire:model="issue_date" class="w-full rounded-lg border-slate-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Valid Until</label>
                    <input type="date" wire:model="valid_until" class="w-full rounded-lg border-slate-200">
                </div>
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium text-slate-700">Line Items</label>
                    <button type="button" wire:click="addLine" class="text-xs text-indigo-600">+ Add Line</button>
                </div>
                @foreach ($lines as $i => $line)
                    <div class="grid grid-cols-12 gap-2 mb-2">
                        <input type="text" wire:model="lines.{{ $i }}.description" placeholder="Description" class="col-span-6 rounded-lg border-slate-200 text-sm">
                        <input type="number" wire:model="lines.{{ $i }}.quantity" step="0.01" class="col-span-2 rounded-lg border-slate-200 text-sm">
                        <input type="number" wire:model="lines.{{ $i }}.unit_price" step="0.01" class="col-span-3 rounded-lg border-slate-200 text-sm">
                        @if (count($lines) > 1)
                            <button type="button" wire:click="removeLine({{ $i }})" class="col-span-1 text-red-500 text-sm">×</button>
                        @endif
                    </div>
                @endforeach
            </div>
            <textarea wire:model="notes" rows="2" placeholder="Notes..." class="w-full rounded-lg border-slate-200 text-sm"></textarea>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg">Create Quote</button>
        </form>
    </div>
