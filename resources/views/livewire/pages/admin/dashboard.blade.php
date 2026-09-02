<?php

use App\Domain\Analytics\Models\PageView;
use App\Domain\Billing\Models\MpesaTransaction;
use App\Domain\Organizations\Models\Organization;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public function with(): array
    {
        $activeOrgs = Organization::where('status', Organization::STATUS_ACTIVE)->count();
        $trialOrgs = Organization::where('status', Organization::STATUS_TRIAL)->count();
        $totalOrgs = Organization::count();

        $mrr = Organization::where('status', Organization::STATUS_ACTIVE)
            ->with('subscriptionPlan')
            ->get()
            ->sum(fn ($org) => (float) ($org->subscriptionPlan?->price ?? 0));

        $expiringTrials = Organization::where('status', Organization::STATUS_TRIAL)
            ->where('trial_ends_at', '<=', now()->addDays(7))
            ->orderBy('trial_ends_at')
            ->limit(5)
            ->get();

        $recentOrgs = Organization::latest()->limit(5)->get();

        $recentPayments = MpesaTransaction::with(['organization', 'subscriptionPlan'])
            ->where('status', MpesaTransaction::STATUS_COMPLETED)
            ->latest()
            ->limit(5)
            ->get();

        $traffic = PageView::summary();

        return compact(
            'activeOrgs',
            'trialOrgs',
            'totalOrgs',
            'mrr',
            'expiringTrials',
            'recentOrgs',
            'recentPayments',
            'traffic'
        );
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Platform Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Overview of all organisations and billing activity.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
            <div class="text-sm text-slate-500">Total Organisations</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $totalOrgs }}</div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
            <div class="text-sm text-slate-500">Active Subscriptions</div>
            <div class="text-2xl font-bold text-green-600 mt-1">{{ $activeOrgs }}</div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
            <div class="text-sm text-slate-500">On Trial</div>
            <div class="text-2xl font-bold text-blue-600 mt-1">{{ $trialOrgs }}</div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
            <div class="text-sm text-slate-500">Monthly Recurring Revenue</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($mrr, 2) }}</div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
            <div class="text-sm text-slate-500">Site visitors today</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($traffic['visitors_today']) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ number_format($traffic['views_today']) }} views · <a href="{{ route('admin.analytics') }}" wire:navigate class="text-indigo-600 hover:underline">Analytics</a></div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
            <div class="text-sm text-slate-500">Visitors (7 days)</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($traffic['visitors_7d']) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ number_format($traffic['views_7d']) }} page views</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-5 border-b border-slate-100">
                <h2 class="font-semibold text-slate-900">Recent Signups</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentOrgs as $org)
                    <a href="{{ route('admin.organizations.show', $org) }}" wire:navigate class="block p-4 hover:bg-slate-50">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-medium text-slate-900">{{ $org->name }}</div>
                                <div class="text-sm text-slate-500">{{ $org->email }}</div>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600">{{ $org->status }}</span>
                        </div>
                    </a>
                @empty
                    <div class="p-4 text-sm text-slate-500">No organisations yet.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-5 border-b border-slate-100">
                <h2 class="font-semibold text-slate-900">Trials Expiring Soon</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($expiringTrials as $org)
                    <a href="{{ route('admin.organizations.show', $org) }}" wire:navigate class="block p-4 hover:bg-slate-50">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-medium text-slate-900">{{ $org->name }}</div>
                                <div class="text-sm text-slate-500">Expires {{ $org->trial_ends_at?->diffForHumans() }}</div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-4 text-sm text-slate-500">No trials expiring in the next 7 days.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
