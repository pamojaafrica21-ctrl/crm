<?php

use App\Application\Billing\StripePlanSyncService;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Organizations\Services\OrganizationContext;
use App\Infrastructure\Billing\MpesaService;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $selectedPlanId = null;

    public string $mpesaPhone = '';

    public ?string $message = null;

    public ?string $error = null;

    public bool $sendingMpesa = false;

    public bool $canManage = false;

    public bool $canView = false;

    public function mount(): void
    {
        $user = auth()->user();
        $org = app(OrganizationContext::class)->resolveForUser($user);

        $this->canManage = (bool) $user?->canManageBilling();
        $this->canView = (bool) $user?->canViewBilling();

        if (! $this->canView && (! $org || $org->canAccessPlatform())) {
            abort(403, 'You do not have access to billing.');
        }

        $this->selectedPlanId = $org?->subscription_plan_id
            ?? SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->value('id');
        $this->mpesaPhone = $user->phone ?? '';

        if (request('success')) {
            $this->message = 'Payment successful! Your subscription is now active.';
        }
    }

    public function with(): array
    {
        $org = app(OrganizationContext::class)->resolveForUser(auth()->user());
        $mpesa = app(MpesaService::class);
        $subscription = $org?->subscription('default');

        return [
            'organization' => $org?->load('subscriptionPlan'),
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get(),
            'mpesaConfigured' => $mpesa->isConfigured(),
            'stripeSubscription' => $subscription,
            'onGracePeriod' => (bool) $subscription?->onGracePeriod(),
            'endsAt' => $subscription?->ends_at,
        ];
    }

    public function subscribeStripe(int $planId, StripePlanSyncService $stripeService): void
    {
        $this->ensureCanManage();
        $this->message = null;
        $this->error = null;

        $org = app(OrganizationContext::class)->resolveForUser(auth()->user());
        $plan = SubscriptionPlan::findOrFail($planId);

        if (! $org) {
            $this->error = 'Organisation not found.';

            return;
        }

        try {
            $stripeService->configureStripe();
            $stripeService->syncPlan($plan);

            if (! $plan->stripe_price_id) {
                $this->error = 'Stripe is not configured. Contact platform support.';

                return;
            }

            if ($org->subscribed('default')) {
                $org->subscription('default')->swap($plan->stripe_price_id);
                $org->update([
                    'subscription_plan_id' => $plan->id,
                    'status' => Organization::STATUS_ACTIVE,
                    'trial_ends_at' => null,
                ]);
                $this->message = 'Plan updated to '.$plan->name.'.';

                return;
            }

            $checkout = $org->newSubscription('default', $plan->stripe_price_id)
                ->trialDays($org->isOnTrial() ? max(0, (int) now()->diffInDays($org->trial_ends_at, false)) : 0)
                ->checkout([
                    'success_url' => route('billing').'?success=1',
                    'cancel_url' => route('billing').'?cancelled=1',
                ]);

            $this->redirect($checkout->url);
        } catch (\Throwable $e) {
            $this->error = 'Unable to start checkout: '.$e->getMessage();
        }
    }

    public function changePlan(int $planId, StripePlanSyncService $stripeService): void
    {
        $this->subscribeStripe($planId, $stripeService);
    }

    public function cancelSubscription(): void
    {
        $this->ensureCanManage();
        $this->message = null;
        $this->error = null;

        $org = app(OrganizationContext::class)->resolveForUser(auth()->user());

        if (! $org) {
            $this->error = 'Organisation not found.';

            return;
        }

        try {
            if ($org->subscribed('default') && ! $org->subscription('default')->canceled()) {
                $org->subscription('default')->cancel();
                $ends = $org->subscription('default')->ends_at?->format('M j, Y');
                $this->message = $ends
                    ? "Subscription cancelled. You keep access until {$ends}."
                    : 'Subscription cancelled.';

                return;
            }

            $org->update(['status' => Organization::STATUS_CANCELLED]);
            $this->message = 'Subscription cancelled. Platform access is now restricted.';
        } catch (\Throwable $e) {
            $this->error = 'Unable to cancel: '.$e->getMessage();
        }
    }

    public function resumeSubscription(): void
    {
        $this->ensureCanManage();
        $this->message = null;
        $this->error = null;

        $org = app(OrganizationContext::class)->resolveForUser(auth()->user());

        if (! $org) {
            $this->error = 'Organisation not found.';

            return;
        }

        try {
            $subscription = $org->subscription('default');

            if ($subscription?->onGracePeriod()) {
                $subscription->resume();
                $org->update(['status' => Organization::STATUS_ACTIVE]);
                $this->message = 'Subscription resumed successfully.';

                return;
            }

            $this->error = 'There is no cancelled subscription to resume. Choose a plan to subscribe again.';
        } catch (\Throwable $e) {
            $this->error = 'Unable to resume: '.$e->getMessage();
        }
    }

    public function payWithMpesa(MpesaService $mpesa): void
    {
        $this->ensureCanManage();
        $this->message = null;
        $this->error = null;
        $this->sendingMpesa = true;

        try {
            $this->validate([
                'selectedPlanId' => ['required', 'exists:subscription_plans,id'],
                'mpesaPhone' => ['required', 'string', 'min:9'],
            ]);

            $org = app(OrganizationContext::class)->resolveForUser(auth()->user());
            $plan = SubscriptionPlan::findOrFail($this->selectedPlanId);

            if (! $org) {
                $this->error = 'Organisation not found.';

                return;
            }

            if (! $mpesa->isConfigured()) {
                $this->error = 'M-Pesa is not configured. Ask the platform admin to set Daraja credentials in Admin → Settings.';

                return;
            }

            $transaction = $mpesa->initiateStkPush($org, $plan, $this->mpesaPhone);

            if ($transaction->status === 'pending') {
                $this->message = 'STK push sent to '.$transaction->phone.'. Enter your M-Pesa PIN on your phone to complete payment of KES '.number_format((float) $transaction->amount, 0).'.';
            } else {
                $this->error = $transaction->result_description ?? 'Payment initiation failed.';
            }
        } finally {
            $this->sendingMpesa = false;
        }
    }

    private function ensureCanManage(): void
    {
        if (! auth()->user()?->canManageBilling()) {
            abort(403, 'You do not have permission to manage billing.');
        }
    }
}; ?>

<div class="space-y-6 max-w-4xl mx-auto">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Billing & Subscription</h1>
        <p class="mt-1 text-sm text-slate-500">Manage your organisation's subscription plan.</p>
    </div>

    @if (session('billing_notice'))
        <div class="bg-amber-50 text-amber-800 px-4 py-3 rounded-lg text-sm">{{ session('billing_notice') }}</div>
    @endif

    @if ($message)
        <div class="bg-green-50 text-green-700 px-4 py-3 rounded-lg text-sm">{{ $message }}</div>
    @endif

    @if ($error)
        <div class="bg-red-50 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $error }}</div>
    @endif

    @if ($organization)
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <h2 class="font-semibold text-slate-900 mb-4">Current Status</h2>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">Organisation</dt><dd class="font-medium">{{ $organization->name }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="font-medium">{{ ucfirst(str_replace('_', ' ', $organization->status)) }}</dd></div>
                <div><dt class="text-slate-500">Current Plan</dt><dd class="font-medium">{{ $organization->subscriptionPlan?->name ?? 'None' }}</dd></div>
                <div><dt class="text-slate-500">Trial Ends</dt><dd class="font-medium">{{ $organization->trial_ends_at?->format('M j, Y') ?? '—' }}</dd></div>
                @if ($onGracePeriod && $endsAt)
                    <div class="col-span-2">
                        <dt class="text-slate-500">Access until</dt>
                        <dd class="font-medium text-amber-700">{{ $endsAt->format('M j, Y') }} (cancellation pending)</dd>
                    </div>
                @endif
            </dl>

            @if ($canManage)
                <div class="mt-5 flex flex-wrap gap-3">
                    @if ($onGracePeriod)
                        <button wire:click="resumeSubscription" wire:confirm="Resume this subscription?"
                                class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                            Resume subscription
                        </button>
                    @elseif ($organization->subscribed('default') || $organization->status === 'active')
                        <button wire:click="cancelSubscription" wire:confirm="Cancel at the end of the current billing period?"
                                class="px-4 py-2 bg-white text-red-600 text-sm font-medium rounded-lg border border-red-200 hover:bg-red-50">
                            Cancel subscription
                        </button>
                    @endif
                </div>
            @endif
        </div>

        @if (! $organization->canAccessPlatform())
            <div class="bg-orange-50 text-orange-800 px-4 py-3 rounded-lg text-sm">
                Your trial or subscription has expired. Please subscribe to continue using the platform.
            </div>
        @endif

        @if (! $canManage)
            <div class="bg-slate-50 text-slate-600 px-4 py-3 rounded-lg text-sm border border-slate-100">
                You can view billing status, but only the organisation owner or staff with billing permissions can change plans or cancel.
            </div>
        @else
            <div>
                <h2 class="font-semibold text-slate-900 mb-1">Plans</h2>
                <p class="text-sm text-slate-500 mb-4">
                    Subscribe, upgrade, or downgrade. Stripe subscriptions switch instantly; M-Pesa activates after payment.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach ($plans as $plan)
                        @php
                            $isCurrent = $organization->subscription_plan_id === $plan->id;
                            $currentPrice = (float) ($organization->subscriptionPlan?->price ?? 0);
                            $planPrice = (float) $plan->price;
                            $actionLabel = 'Subscribe with Stripe';
                            if ($organization->subscribed('default')) {
                                if ($isCurrent) {
                                    $actionLabel = 'Current plan';
                                } elseif ($planPrice > $currentPrice) {
                                    $actionLabel = 'Upgrade';
                                } elseif ($planPrice < $currentPrice) {
                                    $actionLabel = 'Downgrade';
                                } else {
                                    $actionLabel = 'Switch plan';
                                }
                            }
                        @endphp
                        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 {{ $isCurrent ? 'ring-2 ring-indigo-500' : '' }}">
                            <h3 class="font-semibold text-slate-900">{{ $plan->name }}</h3>
                            @if ($plan->badge())
                                <span class="inline-block mt-1 text-xs px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full">{{ $plan->badge() }}</span>
                            @endif
                            <p class="text-2xl font-bold text-indigo-600 mt-2">{{ $plan->formattedPrice() }}<span class="text-sm text-slate-500 font-normal">/{{ $plan->interval }}</span></p>
                            <p class="text-sm text-slate-500 mt-2">{{ $plan->description }}</p>
                            @if ($plan->highlightItems())
                                <ul class="mt-3 space-y-1 text-sm text-slate-600">
                                    @foreach ($plan->highlightItems() as $item)
                                        <li>✓ {{ $item }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            <div class="mt-4 space-y-2">
                                <button
                                    wire:click="changePlan({{ $plan->id }})"
                                    @disabled($isCurrent && $organization->subscribed('default'))
                                    class="w-full px-4 py-2 text-sm font-medium rounded-lg {{ $isCurrent && $organization->subscribed('default') ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-indigo-600 text-white hover:bg-indigo-700' }}">
                                    {{ $actionLabel }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="font-semibold text-slate-900 mb-2">Pay with M-Pesa</h2>
                <p class="text-sm text-slate-500 mb-4">Send an STK push to upgrade, downgrade, or activate a plan via M-Pesa.</p>

                @if (! $mpesaConfigured)
                    <div class="bg-amber-50 text-amber-800 px-4 py-3 rounded-lg text-sm mb-4">
                        M-Pesa credentials are not configured yet. Ask the platform admin to add Daraja keys under Admin → Settings.
                    </div>
                @endif

                <form wire:submit="payWithMpesa" class="space-y-4 max-w-md">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Select Plan</label>
                        <select wire:model="selectedPlanId" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" required>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->formattedPrice() }}</option>
                            @endforeach
                        </select>
                        @error('selectedPlanId') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">M-Pesa Phone Number</label>
                        <input type="text" wire:model="mpesaPhone" placeholder="07XXXXXXXX or 2547XXXXXXXX" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" required>
                        @error('mpesaPhone') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="payWithMpesa"
                            class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="payWithMpesa">Send STK Push</span>
                        <span wire:loading wire:target="payWithMpesa">Sending STK push...</span>
                    </button>
                </form>
            </div>
        @endif
    @endif
</div>
