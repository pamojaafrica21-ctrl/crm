<?php

use App\Application\Dashboard\KpiService;
use Livewire\Volt\Component;

new class extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->authorize('dashboard.view');
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function with(): array
    {
        $from = \Carbon\Carbon::parse($this->dateFrom)->startOfDay();
        $to = \Carbon\Carbon::parse($this->dateTo)->endOfDay();
        $kpi = app(KpiService::class);

        return [
            'metrics' => $kpi->getDashboardMetrics($from, $to),
            'charts' => $kpi->getDashboardCharts($from, $to),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Performance Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Organisation overview for the selected dates.</p>
        </div>
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

    <div wire:key="dashboard-charts-{{ $dateFrom }}-{{ $dateTo }}" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-slate-900">Revenue trend</h2>
                <p class="text-xs text-slate-500 mt-0.5">Invoiced amounts over the selected period</p>
            </div>
            @if (collect($charts['revenue_trend']['values'] ?? [])->sum() <= 0)
                <div class="h-64 flex items-center justify-center text-sm text-slate-400">No invoice revenue in this period</div>
            @else
                <div class="h-64" wire:ignore
                     x-data
                     x-init="
                        const existing = Chart.getChart($refs.canvas);
                        if (existing) existing.destroy();
                        new Chart($refs.canvas, {
                            type: 'line',
                            data: {
                                labels: @js($charts['revenue_trend']['labels']),
                                datasets: [{
                                    label: 'Revenue',
                                    data: @js($charts['revenue_trend']['values']),
                                    borderColor: '#4f46e5',
                                    backgroundColor: 'rgba(79, 70, 229, 0.12)',
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 3,
                                    pointBackgroundColor: '#4f46e5',
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { callback: (value) => '$' + value }
                                    },
                                    x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } }
                                }
                            }
                        });
                     ">
                    <canvas x-ref="canvas"></canvas>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-slate-900">Paid vs outstanding</h2>
                <p class="text-xs text-slate-500 mt-0.5">How much of invoiced revenue has been collected</p>
            </div>
            @if (collect($charts['invoices_overview']['values'] ?? [])->sum() <= 0)
                <div class="h-64 flex items-center justify-center text-sm text-slate-400">No invoice amounts in this period</div>
            @else
                <div class="h-64" wire:ignore
                     x-data
                     x-init="
                        const existing = Chart.getChart($refs.canvas);
                        if (existing) existing.destroy();
                        new Chart($refs.canvas, {
                            type: 'doughnut',
                            data: {
                                labels: @js($charts['invoices_overview']['labels']),
                                datasets: [{
                                    data: @js($charts['invoices_overview']['values']),
                                    backgroundColor: ['#16a34a', '#f97316'],
                                    borderWidth: 0,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'bottom' },
                                    tooltip: {
                                        callbacks: {
                                            label: (ctx) => ctx.label + ': $' + Number(ctx.raw).toLocaleString()
                                        }
                                    }
                                }
                            }
                        });
                     ">
                    <canvas x-ref="canvas"></canvas>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-slate-900">Quotes by status</h2>
                <p class="text-xs text-slate-500 mt-0.5">Pipeline breakdown for quotes issued in the period</p>
            </div>
            @if (collect($charts['quotes_by_status']['values'] ?? [])->sum() <= 0)
                <div class="h-64 flex items-center justify-center text-sm text-slate-400">No quotes in this period</div>
            @else
                <div class="h-64" wire:ignore
                     x-data
                     x-init="
                        const existing = Chart.getChart($refs.canvas);
                        if (existing) existing.destroy();
                        new Chart($refs.canvas, {
                            type: 'bar',
                            data: {
                                labels: @js($charts['quotes_by_status']['labels']),
                                datasets: [{
                                    label: 'Quotes',
                                    data: @js($charts['quotes_by_status']['values']),
                                    backgroundColor: '#4f46e5',
                                    borderRadius: 8,
                                    maxBarThickness: 48,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                            }
                        });
                     ">
                    <canvas x-ref="canvas"></canvas>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-slate-900">Payments by method</h2>
                <p class="text-xs text-slate-500 mt-0.5">How customers paid during the selected dates</p>
            </div>
            @if (collect($charts['payments_by_method']['values'] ?? [])->sum() <= 0)
                <div class="h-64 flex items-center justify-center text-sm text-slate-400">No payments recorded in this period</div>
            @else
                <div class="h-64" wire:ignore
                     x-data
                     x-init="
                        const existing = Chart.getChart($refs.canvas);
                        if (existing) existing.destroy();
                        new Chart($refs.canvas, {
                            type: 'bar',
                            data: {
                                labels: @js($charts['payments_by_method']['labels']),
                                datasets: [{
                                    label: 'Amount',
                                    data: @js($charts['payments_by_method']['values']),
                                    backgroundColor: ['#0f766e', '#4f46e5', '#ca8a04'],
                                    borderRadius: 8,
                                    maxBarThickness: 48,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { callback: (value) => '$' + value }
                                    }
                                }
                            }
                        });
                     ">
                    <canvas x-ref="canvas"></canvas>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5 xl:col-span-2">
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-slate-900">Tasks by status</h2>
                <p class="text-xs text-slate-500 mt-0.5">Workload created in the selected period</p>
            </div>
            @if (collect($charts['tasks_by_status']['values'] ?? [])->sum() <= 0)
                <div class="h-64 flex items-center justify-center text-sm text-slate-400">No tasks created in this period</div>
            @else
                <div class="h-64 max-w-xl mx-auto" wire:ignore
                     x-data
                     x-init="
                        const existing = Chart.getChart($refs.canvas);
                        if (existing) existing.destroy();
                        new Chart($refs.canvas, {
                            type: 'doughnut',
                            data: {
                                labels: @js($charts['tasks_by_status']['labels']),
                                datasets: [{
                                    data: @js($charts['tasks_by_status']['values']),
                                    backgroundColor: ['#94a3b8', '#4f46e5', '#16a34a', '#ef4444'],
                                    borderWidth: 0,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { position: 'bottom' } }
                            }
                        });
                     ">
                    <canvas x-ref="canvas"></canvas>
                </div>
            @endif
        </div>
    </div>
</div>
