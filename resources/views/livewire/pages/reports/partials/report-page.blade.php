<div class="space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <a href="{{ route('reports.index') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← All reports</a>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $pageTitle }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $pageDescription }}</p>
        </div>
        @can('reports.export')
            <button type="button" wire:click="exportCsv"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shrink-0">
                Download CSV
            </button>
        @endcan
    </div>

    @if (count($reportTabs) > 1)
        <div class="flex flex-wrap gap-2">
            @foreach ($reportTabs as $tab)
                <button type="button"
                        wire:click="selectReport('{{ $tab['key'] }}')"
                        class="px-3 py-1.5 text-sm rounded-full border transition-colors
                               {{ $activeReport === $tab['key']
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                    {{ $tab['name'] }}
                </button>
            @endforeach
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border-slate-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">To</label>
                <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border-slate-200 text-sm">
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="setRange('month')" class="px-3 py-2 text-sm border border-slate-200 rounded-lg hover:bg-slate-50">This month</button>
                <button type="button" wire:click="setRange('last30')" class="px-3 py-2 text-sm border border-slate-200 rounded-lg hover:bg-slate-50">Last 30 days</button>
                <button type="button" wire:click="setRange('quarter')" class="px-3 py-2 text-sm border border-slate-200 rounded-lg hover:bg-slate-50">This quarter</button>
                <button type="button" wire:click="setRange('year')" class="px-3 py-2 text-sm border border-slate-200 rounded-lg hover:bg-slate-50">This year</button>
            </div>
        </div>
        @if (($catalogEntry['description'] ?? '') !== '')
            <p class="mt-3 text-sm text-slate-500">{{ $catalogEntry['description'] }}</p>
        @endif
        @if ($errorMessage !== '')
            <p class="mt-2 text-sm text-red-600">{{ $errorMessage }}</p>
        @endif
    </div>

    @if ($errorMessage === '')
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $reportTitle }}</h2>
                    <p class="text-sm text-slate-500">
                        {{ \Carbon\Carbon::parse($dateFrom)->format('M j, Y') }}
                        – {{ \Carbon\Carbon::parse($dateTo)->format('M j, Y') }}
                    </p>
                </div>
                <div class="text-sm text-slate-500">{{ count($rows) }} {{ count($rows) === 1 ? 'result' : 'results' }}</div>
            </div>

            @if ($summary !== [])
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-px bg-slate-100 border-b border-slate-100">
                    @foreach ($summary as $label => $value)
                        <div class="bg-white px-5 py-4">
                            <div class="text-sm text-slate-500">{{ $label }}</div>
                            <div class="mt-1 text-xl font-bold text-slate-900">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($charts !== [])
            <div wire:key="report-charts-{{ $activeReport }}-{{ $dateFrom }}-{{ $dateTo }}"
                 class="grid grid-cols-1 {{ count($charts) > 1 ? 'xl:grid-cols-2' : '' }} gap-6">
                @foreach ($charts as $index => $chart)
                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                        <div class="mb-4">
                            <h2 class="text-sm font-semibold text-slate-900">{{ $chart['title'] }}</h2>
                        </div>
                        @if (collect($chart['values'] ?? [])->sum() <= 0)
                            <div class="h-64 flex items-center justify-center text-sm text-slate-400">No data for this chart</div>
                        @else
                            <div class="h-64" wire:ignore
                                 x-data
                                 x-init="
                                    const existing = Chart.getChart($refs.canvas);
                                    if (existing) existing.destroy();
                                    const type = @js($chart['type']);
                                    const labels = @js($chart['labels']);
                                    const values = @js($chart['values']);
                                    const colors = ['#4f46e5','#16a34a','#f97316','#0ea5e9','#a855f7','#ef4444','#14b8a6','#eab308'];
                                    new Chart($refs.canvas, {
                                        type,
                                        data: {
                                            labels,
                                            datasets: [{
                                                label: @js($chart['title']),
                                                data: values,
                                                backgroundColor: type === 'line' ? 'rgba(79,70,229,0.12)' : colors,
                                                borderColor: type === 'line' ? '#4f46e5' : colors,
                                                borderWidth: type === 'line' ? 2 : 0,
                                                fill: type === 'line',
                                                tension: 0.35,
                                                pointRadius: type === 'line' ? 3 : 0,
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: {
                                                legend: { display: type !== 'bar' && type !== 'line', position: 'bottom' }
                                            },
                                            scales: type === 'doughnut' ? {} : {
                                                y: { beginAtZero: true },
                                                x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } }
                                            }
                                        }
                                    });
                                 ">
                                <canvas x-ref="canvas"></canvas>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @foreach ($breakdowns as $breakdown)
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-900">{{ $breakdown['title'] }}</h2>
                </div>
                <div class="overflow-x-auto">
                    @if (empty($breakdown['rows']))
                        <div class="px-5 py-8 text-center text-sm text-slate-500">No breakdown data.</div>
                    @else
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    @foreach ($breakdown['columns'] as $column)
                                        <th class="text-left px-5 py-3 font-medium whitespace-nowrap">{{ $column }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($breakdown['rows'] as $row)
                                    <tr class="hover:bg-slate-50">
                                        @foreach ($row as $cell)
                                            <td class="px-5 py-3 text-slate-700 whitespace-nowrap">{{ $cell }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Detail</h2>
            </div>
            <div class="overflow-x-auto">
                @if (count($rows) === 0)
                    <div class="px-5 py-12 text-center text-sm text-slate-500">
                        Nothing found for this report and date range.
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                @foreach ($columns as $column)
                                    <th class="text-left px-5 py-3 font-medium whitespace-nowrap">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-slate-50">
                                    @foreach ($row as $cell)
                                        <td class="px-5 py-3 text-slate-700 whitespace-nowrap">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endif
</div>
