<?php

use App\Domain\Billing\Models\MpesaTransaction;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Organizations\Models\Organization;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public Organization $organization;

    public ?int $subscription_plan_id = null;
    public int $trial_extension_days = 7;
    public string $status = '';

    public function mount(Organization $organization): void
    {
        $this->organization = $organization->load(['subscriptionPlan', 'users', 'properties']);
        $this->subscription_plan_id = $organization->subscription_plan_id;
        $this->status = $organization->status;
    }

    public function plans()
    {
        return SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
    }

    public function updatePlan(): void
    {
        $this->validate([
            'subscription_plan_id' => ['nullable', 'exists:subscription_plans,id'],
            'status' => ['required', 'in:trial,active,past_due,suspended,cancelled'],
        ]);

        $this->organization->update([
            'subscription_plan_id' => $this->subscription_plan_id,
            'status' => $this->status,
        ]);

        session()->flash('status', 'Organisation updated.');
        $this->organization->refresh();
    }

    public function extendTrial(): void
    {
        $this->validate(['trial_extension_days' => ['required', 'integer', 'min:1', 'max:365']]);

        $endsAt = $this->organization->trial_ends_at && $this->organization->trial_ends_at->isFuture()
            ? $this->organization->trial_ends_at
            : now();

        $this->organization->update([
            'status' => Organization::STATUS_TRIAL,
            'trial_ends_at' => $endsAt->addDays($this->trial_extension_days),
        ]);

        session()->flash('status', 'Trial extended by '.$this->trial_extension_days.' days.');
        $this->organization->refresh();
    }

    public function deleteOrganization(\App\Application\Organizations\DeleteOrganizationAction $action): void
    {
        $action->execute($this->organization);

        session()->flash('status', 'Organisation deleted successfully.');
        $this->redirect(route('admin.organizations.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'payments' => MpesaTransaction::where('organization_id', $this->organization->id)
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('admin.organizations.index') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">&larr; Back</a>
        <div class="flex gap-2">
            <a href="{{ route('admin.organizations.edit', $organization) }}" wire:navigate
               class="px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700">
                Edit
            </a>
            <button
                wire:click="deleteOrganization"
                wire:confirm="Delete {{ $organization->name }}? This will permanently remove the organisation and its users."
                class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                Delete
            </button>
        </div>
    </div>

    @if (session('status'))
        <div class="bg-green-50 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
    @endif

    <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ $organization->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $organization->email }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="font-semibold text-slate-900 mb-4">Details</h2>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-slate-500">Status</dt><dd class="font-medium">{{ ucfirst(str_replace('_', ' ', $organization->status)) }}</dd></div>
                    <div><dt class="text-slate-500">Plan</dt><dd class="font-medium">{{ $organization->subscriptionPlan?->name ?? 'None' }}</dd></div>
                    <div><dt class="text-slate-500">Trial Ends</dt><dd class="font-medium">{{ $organization->trial_ends_at?->format('M j, Y g:i A') ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Created</dt><dd class="font-medium">{{ $organization->created_at->format('M j, Y') }}</dd></div>
                    <div><dt class="text-slate-500">Phone</dt><dd class="font-medium">{{ $organization->phone ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Stripe Customer</dt><dd class="font-medium">{{ $organization->stripe_id ?? '—' }}</dd></div>
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="font-semibold text-slate-900 mb-4">Users ({{ $organization->users->count() }})</h2>
                <div class="divide-y divide-slate-100">
                    @foreach ($organization->users as $user)
                        <div class="py-2 flex justify-between text-sm">
                            <span>{{ $user->name }} ({{ $user->email }})</span>
                            <span class="text-slate-500">{{ $user->roles->first()?->name ?? 'No role' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="font-semibold text-slate-900 mb-4">Recent Payments</h2>
                @forelse ($payments as $payment)
                    <div class="py-2 flex justify-between text-sm border-b border-slate-50 last:border-0">
                        <span>M-Pesa — {{ $payment->mpesa_receipt_number ?? 'Pending' }}</span>
                        <span>{{ strtoupper($payment->currency) }} {{ number_format($payment->amount, 2) }} — {{ $payment->status }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No payments recorded.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="font-semibold text-slate-900 mb-4">Manage Subscription</h2>
                <form wire:submit="updatePlan" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Plan</label>
                        <select wire:model="subscription_plan_id" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                            <option value="">No plan</option>
                            @foreach ($this->plans() as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </div>
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
                    <button type="submit" class="w-full px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700">
                        Update
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="font-semibold text-slate-900 mb-4">Extend Trial</h2>
                <form wire:submit="extendTrial" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Additional Days</label>
                        <input type="number" wire:model="trial_extension_days" min="1" max="365" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                        Extend Trial
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
