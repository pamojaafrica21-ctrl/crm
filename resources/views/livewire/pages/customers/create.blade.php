<?php

use App\Domain\Customers\Models\Customer;
use App\Domain\Properties\Services\PropertyContext;
use Livewire\Volt\Component;

new class extends Component
{
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $nationality = '';
    public string $company = '';
    public string $vip_level = '';
    public string $address = '';

    public function save(): void
    {
        $this->authorize('customers.create');

        $validated = $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:255',
            'vip_level' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $customer = Customer::create(array_merge($validated, [
            'property_id' => app(PropertyContext::class)->id(),
            'source' => 'crm',
        ]));

        $this->redirect(route('customers.show', $customer), navigate: true);
    }
}; ?>

<div class="max-w-2xl space-y-6">
        <h1 class="text-2xl font-bold text-slate-900">Add Customer</h1>
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">First Name *</label>
                    <input type="text" wire:model="first_name" class="w-full rounded-lg border-slate-200">
                    @error('first_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Last Name *</label>
                    <input type="text" wire:model="last_name" class="w-full rounded-lg border-slate-200">
                    @error('last_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" wire:model="email" class="w-full rounded-lg border-slate-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                    <input type="text" wire:model="phone" class="w-full rounded-lg border-slate-200">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Company</label>
                    <input type="text" wire:model="company" class="w-full rounded-lg border-slate-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">VIP Level</label>
                    <input type="text" wire:model="vip_level" class="w-full rounded-lg border-slate-200">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                <textarea wire:model="address" rows="2" class="w-full rounded-lg border-slate-200"></textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Save Customer</button>
                <a href="{{ route('customers.index') }}" wire:navigate class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">Cancel</a>
            </div>
        </form>
    </div>
