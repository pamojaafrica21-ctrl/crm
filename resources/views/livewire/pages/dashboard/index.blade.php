<?php

use App\Application\Dashboard\KpiService;
use Livewire\Volt\Component;

new class extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function with(): array
    {
        return [
            'metrics' => app(KpiService::class)->getDashboardMetrics(
                \Carbon\Carbon::parse($this->dateFrom),
                \Carbon\Carbon::parse($this->dateTo)
            ),
        ];
    }
}; ?>

<div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">Performance Dashboard</h1>
            <div class="flex items-center gap-3">
                <input type="date" wire:model.live="dateFrom" class="rounded-lg border-slate-200 text-sm">
                <span class="text-slate-400">to</span>
                <input type="date" wire:model.live="dateTo" class="rounded-lg border-slate-200 text-sm">
            </div>
        </div>

        @php $m = $metrics; @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                <div class="text-sm text-slate-500">Revenue</div>
                <div class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($m['revenue'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                <div class="text-sm text-slate-500">Outstanding</div>
                <div class="text-2xl font-bold text-orange-600 mt-1">${{ number_format($m['outstanding'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                <div class="text-sm text-slate-500">Bookings</div>
                <div class="text-2xl font-bold text-slate-900 mt-1">{{ $m['bookings'] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                <div class="text-sm text-slate-500">Customers</div>
                <div class="text-2xl font-bold text-slate-900 mt-1">{{ $m['customers'] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                <div class="text-sm text-slate-500">Quote Conversion</div>
                <div class="text-2xl font-bold text-green-600 mt-1">{{ $m['conversion_rate'] ?? 0 }}%</div>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                <div class="text-sm text-slate-500">Task Completion</div>
                <div class="text-2xl font-bold text-indigo-600 mt-1">{{ $m['task_completion_rate'] ?? 0 }}%</div>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                <div class="text-sm text-slate-500">Appointments</div>
                <div class="text-2xl font-bold text-slate-900 mt-1">{{ $m['appointments'] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                <div class="text-sm text-slate-500">Completed Appointments</div>
                <div class="text-2xl font-bold text-slate-900 mt-1">{{ $m['appointments_completed'] ?? 0 }}</div>
            </div>
        </div>
    </div>
