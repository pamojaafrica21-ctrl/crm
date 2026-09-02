<?php

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Organizations\Models\Organization;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public Organization $organization;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $status = 'trial';
    public ?int $subscription_plan_id = null;
    public ?string $trial_ends_at = null;

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
        $this->name = $organization->name;
        $this->email = $organization->email;
        $this->phone = $organization->phone ?? '';
        $this->address = $organization->address ?? '';
        $this->status = $organization->status;
        $this->subscription_plan_id = $organization->subscription_plan_id;
        $this->trial_ends_at = $organization->trial_ends_at?->format('Y-m-d\TH:i');
    }

    public function plans()
    {
        return SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:trial,active,past_due,suspended,cancelled'],
            'subscription_plan_id' => ['nullable', 'exists:subscription_plans,id'],
            'trial_ends_at' => ['nullable', 'date'],
        ]);

        $slug = Str::slug($validated['name']);
        $baseSlug = $slug;
        $counter = 1;
        while (
            Organization::where('slug', $slug)
                ->where('id', '!=', $this->organization->id)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        $this->organization->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'address' => $validated['address'] ?: null,
            'status' => $validated['status'],
            'subscription_plan_id' => $validated['subscription_plan_id'],
            'trial_ends_at' => $validated['trial_ends_at'] ?: null,
        ]);

        session()->flash('status', 'Organisation updated successfully.');
        $this->redirect(route('admin.organizations.show', $this->organization), navigate: true);
    }
}; ?>

<div class="space-y-6 max-w-2xl">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.organizations.show', $organization) }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">&larr; Back</a>
    </div>

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Edit Organisation</h1>
        <p class="mt-1 text-sm text-slate-500">Update organisation details and subscription settings.</p>
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

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Status</label>
                <select wire:model="status" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                    <option value="trial">Trial</option>
                    <option value="active">Active</option>
                    <option value="past_due">Past Due</option>
                    <option value="suspended">Suspended</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Plan</label>
                <select wire:model="subscription_plan_id" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                    <option value="">No plan</option>
                    @foreach ($this->plans() as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->formattedPrice() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Trial Ends At</label>
            <input type="datetime-local" wire:model="trial_ends_at" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700">
                Save Changes
            </button>
            <a href="{{ route('admin.organizations.show', $organization) }}" wire:navigate class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">
                Cancel
            </a>
        </div>
    </form>
</div>
