<?php

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerSegment;
use App\Domain\Properties\Services\PropertyContext;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $description = '';
    public string $customerToAdd = '';
    public ?int $selectedSegment = null;

    public function segments()
    {
        return CustomerSegment::withCount('customers')->get();
    }

    public function customers()
    {
        return Customer::orderBy('last_name')->get();
    }

    public function createSegment(): void
    {
        $this->authorize('customers.update');
        $this->validate(['name' => 'required|string|max:255']);

        CustomerSegment::create([
            'property_id' => app(PropertyContext::class)->id(),
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $this->reset(['name', 'description']);
    }

    public function addToSegment(int $segmentId): void
    {
        $this->authorize('customers.update');
        if ($this->customerToAdd) {
            $segment = CustomerSegment::findOrFail($segmentId);
            $segment->customers()->syncWithoutDetaching([$this->customerToAdd]);
            $this->customerToAdd = '';
        }
    }
}; ?>

<div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">Customer Segments</h1>
            <a href="{{ route('customers.index') }}" wire:navigate class="text-sm text-indigo-600">← Customers</a>
        </div>

        @can('customers.update')
            <form wire:submit="createSegment" class="bg-white rounded-xl p-4 border border-slate-100 flex gap-3">
                <input type="text" wire:model="name" placeholder="Segment name" class="flex-1 rounded-lg border-slate-200 text-sm">
                <input type="text" wire:model="description" placeholder="Description" class="flex-1 rounded-lg border-slate-200 text-sm">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Create</button>
            </form>
        @endcan

        <div class="grid gap-4">
            @foreach ($this->segments() as $segment)
                <div class="bg-white rounded-xl p-5 border border-slate-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold">{{ $segment->name }}</h3>
                            <p class="text-sm text-slate-500">{{ $segment->description }}</p>
                            <p class="text-xs text-slate-400 mt-1">{{ $segment->customers_count }} customers</p>
                        </div>
                        @can('customers.update')
                            <div class="flex gap-2">
                                <select wire:model="customerToAdd" class="rounded-lg border-slate-200 text-sm">
                                    <option value="">Add customer...</option>
                                    @foreach ($this->customers() as $c)
                                        <option value="{{ $c->id }}">{{ $c->fullName() }}</option>
                                    @endforeach
                                </select>
                                <button wire:click="addToSegment({{ $segment->id }})" class="px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-lg">Add</button>
                            </div>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    </div>
