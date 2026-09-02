<?php

use Livewire\Volt\Component;

new class extends Component
{
    public function navItems(): array
    {
        return [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'chart'],
            ['route' => 'admin.analytics', 'label' => 'Analytics', 'icon' => 'analytics'],
            ['route' => 'admin.organizations.index', 'label' => 'Organisations', 'icon' => 'orgs'],
            ['route' => 'admin.plans.index', 'label' => 'Plans', 'icon' => 'plans'],
            ['route' => 'admin.homepage.index', 'label' => 'Homepage', 'icon' => 'home'],
            ['route' => 'admin.payments.index', 'label' => 'Payments', 'icon' => 'payments'],
            ['route' => 'admin.settings.index', 'label' => 'Settings', 'icon' => 'settings'],
        ];
    }
}; ?>

<aside class="w-64 bg-slate-900 text-white flex flex-col shrink-0">
    <div class="p-5 border-b border-slate-700">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-3">
            <img src="{{ asset('favicon-32x32.png') }}" alt="" width="36" height="36" class="w-9 h-9 rounded-lg">
            <div>
                <div class="font-semibold text-sm">Admin Centre</div>
                <div class="text-xs text-slate-400">Platform Management</div>
            </div>
        </a>
    </div>

    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        @foreach ($this->navItems() as $item)
            <a href="{{ route($item['route']) }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors
                      {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route'])
                         ? 'bg-violet-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span class="w-5 h-5 flex items-center justify-center opacity-70">
                    @switch($item['icon'])
                        @case('chart') 📊 @break
                        @case('analytics') 👁️ @break
                        @case('orgs') 🏢 @break
                        @case('plans') 💳 @break
                        @case('home') 🏠 @break
                        @case('payments') 💰 @break
                        @case('settings') ⚙️ @break
                    @endswitch
                </span>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</aside>
