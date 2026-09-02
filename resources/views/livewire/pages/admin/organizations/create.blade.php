<?php

use App\Application\Organizations\CreateOrganizationAction;
use App\Domain\Billing\Models\SubscriptionPlan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $owner_name = '';
    public string $owner_email = '';
    public string $owner_password = '';
    public ?int $subscription_plan_id = null;
    public int $trial_days = 14;
    public string $status = 'trial';

    public function mount(): void
    {
        $defaultPlan = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->first();
        $this->subscription_plan_id = $defaultPlan?->id;
        $this->trial_days = $defaultPlan?->trial_days ?? 14;
    }

    public function plans()
    {
        return SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
    }

    public function save(CreateOrganizationAction $action): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],
            'subscription_plan_id' => ['nullable', 'exists:subscription_plans,id'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'status' => ['required', 'in:trial,active,suspended'],
        ]);

        $org = $action->execute($validated);

        session()->flash('status', 'Organisation created successfully.');
        $this->redirect(route('admin.organizations.show', $org), navigate: true);
    }
}; ?>

<div class="space-y-6 max-w-2xl">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Create Organisation</h1>
        <p class="mt-1 text-sm text-slate-500">Provision a new tenant manually.</p>
    </div>

    <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-700">Organisation Name</label>
            <input type="text" wire:model="name" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" required>
            @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" wire:model="email" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" required>
                @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Phone</label>
                <input type="text" wire:model="phone" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Address</label>
            <textarea wire:model="address" rows="2" class="mt-1 block w-full rounded-lg border-slate-200 text-sm"></textarea>
        </div>

        <hr class="border-slate-100">

        <h3 class="font-medium text-slate-900">Owner Account</h3>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Owner Name</label>
                <input type="text" wire:model="owner_name" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" required>
                @error('owner_name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Owner Email</label>
                <input type="email" wire:model="owner_email" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" required>
                @error('owner_email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Owner Password</label>
            <input type="password" wire:model="owner_password" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" required>
            @error('owner_password') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <hr class="border-slate-100">

        <h3 class="font-medium text-slate-900">Subscription</h3>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Plan</label>
                <select wire:model="subscription_plan_id" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                    @foreach ($this->plans() as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->formattedPrice() }}/{{ $plan->interval }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Trial Days</label>
                <input type="number" wire:model="trial_days" min="0" max="365" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Status</label>
            <select wire:model="status" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                <option value="trial">Trial</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700">
                Create Organisation
            </button>
            <a href="{{ route('admin.organizations.index') }}" wire:navigate class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">
                Cancel
            </a>
        </div>
    </form>
</div>
