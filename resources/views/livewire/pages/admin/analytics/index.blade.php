<?php

use App\Domain\Analytics\Models\PageView;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public function with(): array
    {
        return [
            'stats' => PageView::summary(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Site Analytics</h1>
        <p class="mt-1 text-sm text-slate-500">Visits to the public Core CRM marketing site (bots excluded).</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
            <div class="text-sm text-slate-500">Visitors today</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($stats['visitors_today']) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ number_format($stats['views_today']) }} page views</div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
            <div class="text-sm text-slate-500">Last 7 days</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($stats['visitors_7d']) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ number_format($stats['views_7d']) }} page views</div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
            <div class="text-sm text-slate-500">Last 30 days</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($stats['visitors_30d']) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ number_format($stats['views_30d']) }} page views</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-5 border-b border-slate-100">
                <h2 class="font-semibold text-slate-900">Daily traffic (30 days)</h2>
            </div>
            <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                @forelse ($stats['by_day'] as $day)
                    <div class="px-5 py-3 flex items-center justify-between text-sm">
                        <span class="text-slate-700">{{ \Illuminate\Support\Carbon::parse($day->day)->format('M j, Y') }}</span>
                        <span class="text-slate-500">
                            <span class="font-medium text-slate-900">{{ $day->visitors }}</span> visitors ·
                            {{ $day->views }} views
                        </span>
                    </div>
                @empty
                    <div class="p-5 text-sm text-slate-500">No visits recorded yet. Open the public homepage to generate traffic.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-5 border-b border-slate-100">
                <h2 class="font-semibold text-slate-900">Top pages</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($stats['top_paths'] as $row)
                    <div class="px-5 py-3 flex items-center justify-between text-sm gap-4">
                        <code class="text-slate-800 truncate">{{ $row->path }}</code>
                        <span class="shrink-0 text-slate-500">
                            <span class="font-medium text-slate-900">{{ $row->visitors }}</span> · {{ $row->views }}
                        </span>
                    </div>
                @empty
                    <div class="p-5 text-sm text-slate-500">No page data yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
