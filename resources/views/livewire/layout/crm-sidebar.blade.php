<?php

use Livewire\Volt\Component;

new class extends Component
{
    public function navItems(): array
    {
        $items = [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'chart', 'permission' => 'dashboard.view'],
            ['route' => 'customers.index', 'label' => 'Customers', 'icon' => 'users', 'permission' => 'customers.view'],
            ['route' => 'quotes.index', 'label' => 'Quotes', 'icon' => 'document', 'permission' => 'quotes.view'],
            ['route' => 'invoices.index', 'label' => 'Invoices', 'icon' => 'receipt', 'permission' => 'invoices.view'],
            ['route' => 'tasks.index', 'label' => 'Tasks', 'icon' => 'check', 'permission' => 'tasks.view'],
            ['route' => 'appointments.index', 'label' => 'Appointments', 'icon' => 'calendar', 'permission' => 'appointments.view'],
            ['route' => 'targets.index', 'label' => 'Targets', 'icon' => 'target', 'permission' => 'targets.view'],
            ['route' => 'reports.index', 'label' => 'Reports', 'icon' => 'reports', 'permission' => 'reports.view'],
            ['route' => 'staff.index', 'label' => 'Staff', 'icon' => 'staff', 'permission' => 'staff.view'],
            ['route' => 'sync.index', 'label' => 'HMS Sync', 'icon' => 'sync', 'permission' => 'sync.view'],
            ['route' => 'announcements.index', 'label' => 'Announcements', 'icon' => 'bell', 'permission' => 'announcements.view'],
            ['route' => 'billing', 'label' => 'Billing', 'icon' => 'billing', 'permission' => 'billing.view', 'gate' => 'billing'],
        ];

        $items = array_filter($items, function ($item) {
            if (($item['gate'] ?? null) === 'billing') {
                return auth()->user()?->canViewBilling();
            }

            return ! isset($item['permission']) || auth()->user()?->can($item['permission']);
        });

        if (auth()->user()?->is_super_admin) {
            $items[] = ['route' => 'admin.dashboard', 'label' => 'Admin Centre', 'icon' => 'admin', 'permission' => null];
        }

        return $items;
    }
}; ?>

<aside class="w-64 bg-slate-900 text-white flex flex-col shrink-0">
    <div class="p-5 border-b border-slate-700">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
            <img src="{{ asset('favicon-32x32.png') }}" alt="" width="36" height="36" class="w-9 h-9 rounded-lg">
            <div>
                <div class="font-semibold text-sm">{{ config('app.name', 'Core CRM') }}</div>
                <div class="text-xs text-slate-400">Staff portal</div>
            </div>
        </a>
    </div>

    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        @foreach ($this->navItems() as $item)
            <a href="{{ route($item['route']) }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors
                      {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route'])
                         ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span class="w-5 h-5 flex items-center justify-center opacity-70">
                    @switch($item['icon'])
                        @case('chart') 📊 @break
                        @case('users') 👥 @break
                        @case('document') 📄 @break
                        @case('receipt') 🧾 @break
                        @case('check') ✅ @break
                        @case('calendar') 📅 @break
                        @case('target') 🎯 @break
                        @case('reports') 📈 @break
                        @case('staff') 👤 @break
                        @case('sync') 🔄 @break
                        @case('bell') 📢 @break
                        @case('billing') 💳 @break
                        @case('admin') 🛡️ @break
                    @endswitch
                </span>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="p-4 border-t border-slate-700">
        <a href="{{ route('profile') }}" wire:navigate
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors
                  {{ request()->routeIs('profile')
                     ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            ⚙️ Settings
        </a>
    </div>
</aside>
