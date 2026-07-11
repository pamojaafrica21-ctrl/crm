<?php

use App\Domain\Customers\Models\Customer;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public function with(): array
    {
        return [
            'customers' => Customer::query()
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                }))
                ->with('tags')
                ->latest()
                ->paginate(15),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">Customers</h1>
            @can('customers.create')
                <div class="flex gap-2">
                    <a href="{{ route('customers.segments') }}" wire:navigate class="px-4 py-2 border border-slate-200 text-sm font-medium rounded-lg hover:bg-slate-50">Segments</a>
                    <a href="{{ route('customers.create') }}" wire:navigate
                       class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                        Add Customer
                    </a>
                </div>
            @endcan
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-4 border-b border-slate-100">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search by name, email, or phone..."
                       class="w-full max-w-md rounded-lg border-slate-200 text-sm">
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium">Name</th>
                            <th class="text-left px-4 py-3 font-medium">Email</th>
                            <th class="text-left px-4 py-3 font-medium">Phone</th>
                            <th class="text-left px-4 py-3 font-medium">VIP</th>
                            <th class="text-left px-4 py-3 font-medium">Source</th>
                            <th class="text-right px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($customers as $customer)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $customer->fullName() }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $customer->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $customer->phone ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($customer->vip_level)
                                        <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-full text-xs">{{ $customer->vip_level }}</span>
                                    @else — @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-xs uppercase">{{ $customer->source }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('customers.show', $customer) }}" wire:navigate class="text-indigo-600 hover:text-indigo-800">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No customers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $customers->links() }}</div>
        </div>
    </div>
