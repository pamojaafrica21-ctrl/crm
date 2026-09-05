<?php

use App\Application\Reports\ReportService;
use Livewire\Volt\Component;

new class extends Component
{
    public function mount(): void
    {
        $this->authorize('reports.view');
    }

    public function with(): array
    {
        return [
            'categories' => app(ReportService::class)->categories(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Reports</h1>
        <p class="mt-1 text-sm text-slate-500">Open a category for detailed charts, breakdowns, and exportable tables.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($categories as $category)
            <a href="{{ route($category['route']) }}" wire:navigate
               class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 hover:border-indigo-200 hover:shadow-md transition-all group">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 group-hover:text-indigo-700">{{ $category['name'] }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ $category['description'] }}</p>
                    </div>
                    <span class="text-indigo-600 text-sm font-medium shrink-0">Open →</span>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($category['reports'] as $report)
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-600">{{ $report['name'] }}</span>
                    @endforeach
                </div>
            </a>
        @endforeach
    </div>
</div>
