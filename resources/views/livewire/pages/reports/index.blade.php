<?php

use App\Application\Reports\ReportService;
use Carbon\Carbon;
use Livewire\Volt\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component
{
    public string $selectedReport = 'sales_pipeline';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $reportTitle = '';
    public array $columns = [];
    public array $rows = [];
    public array $summary = [];
    public string $errorMessage = '';

    public function mount(): void
    {
        $this->authorize('reports.view');
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->run();
    }

    public function catalog(): array
    {
        return app(ReportService::class)->catalog();
    }

    public function updatedSelectedReport(): void
    {
        $this->run();
    }

    public function updatedDateFrom(): void
    {
        $this->run();
    }

    public function updatedDateTo(): void
    {
        $this->run();
    }

    public function setRange(string $preset): void
    {
        match ($preset) {
            'month' => [
                $this->dateFrom = now()->startOfMonth()->toDateString(),
                $this->dateTo = now()->toDateString(),
            ],
            'last30' => [
                $this->dateFrom = now()->subDays(30)->toDateString(),
                $this->dateTo = now()->toDateString(),
            ],
            'year' => [
                $this->dateFrom = now()->startOfYear()->toDateString(),
                $this->dateTo = now()->toDateString(),
            ],
            default => null,
        };

        $this->run();
    }

    public function run(): void
    {
        $this->authorize('reports.view');
        $this->errorMessage = '';

        if ($this->selectedReport === '' || $this->dateFrom === '' || $this->dateTo === '') {
            return;
        }

        if ($this->dateTo < $this->dateFrom) {
            $this->errorMessage = 'The end date must be on or after the start date.';
            $this->rows = [];
            $this->summary = [];
            $this->columns = [];

            return;
        }

        $result = app(ReportService::class)->generate(
            $this->selectedReport,
            Carbon::parse($this->dateFrom)->startOfDay(),
            Carbon::parse($this->dateTo)->endOfDay(),
        );

        $this->reportTitle = $result['title'];
        $this->columns = $result['columns'];
        $this->rows = $result['rows'];
        $this->summary = $result['summary'];
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('reports.export');
        $this->run();

        $filename = str_replace(' ', '_', strtolower($this->reportTitle ?: 'report'))
            .'_'.$this->dateFrom.'_to_'.$this->dateTo.'.csv';

        $columns = $this->columns;
        $rows = $this->rows;
        $summary = $this->summary;
        $title = $this->reportTitle;
        $dateFrom = $this->dateFrom;
        $dateTo = $this->dateTo;

        return response()->streamDownload(function () use ($columns, $rows, $summary, $title, $dateFrom, $dateTo) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [$title]);
            fputcsv($handle, ['From', $dateFrom, 'To', $dateTo]);
            fputcsv($handle, []);

            foreach ($summary as $label => $value) {
                fputcsv($handle, [$label, $value]);
            }

            if ($summary !== []) {
                fputcsv($handle, []);
            }

            fputcsv($handle, $columns);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Reports</h1>
        <p class="mt-1 text-sm text-slate-500">Pick a report and date range to view the numbers.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="flex-1 min-w-0">
                <label class="block text-sm font-medium text-slate-700 mb-1">Report</label>
                <select wire:model.live="selectedReport" class="w-full rounded-lg border-slate-200 text-sm">
                    @foreach ($this->catalog() as $report)
                        <option value="{{ $report['key'] }}">{{ $report['name'] }}</option>
                    @endforeach
                </select>
            </div>

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
                <button type="button" wire:click="setRange('year')" class="px-3 py-2 text-sm border border-slate-200 rounded-lg hover:bg-slate-50">This year</button>
            </div>

            @can('reports.export')
                <button type="button" wire:click="exportCsv"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shrink-0">
                    Download CSV
                </button>
            @endcan
        </div>

        @if ($selectedReport !== '')
            <p class="mt-3 text-sm text-slate-500">{{ $this->catalog()[$selectedReport]['description'] }}</p>
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
                <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-slate-100 border-b border-slate-100">
                    @foreach ($summary as $label => $value)
                        <div class="bg-white px-5 py-4">
                            <div class="text-sm text-slate-500">{{ $label }}</div>
                            <div class="mt-1 text-xl font-bold text-slate-900">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

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
