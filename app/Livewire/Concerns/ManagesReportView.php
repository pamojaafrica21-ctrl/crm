<?php

namespace App\Livewire\Concerns;

use App\Application\Reports\ReportService;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ManagesReportView
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public string $activeReport = '';

    public string $reportTitle = '';

    public array $columns = [];

    public array $rows = [];

    public array $summary = [];

    public array $breakdowns = [];

    public array $charts = [];

    public string $errorMessage = '';

    abstract protected function defaultReportKey(): string;

    /**
     * @return array<int, string>
     */
    abstract protected function reportKeys(): array;

    protected function initializeReportView(): void
    {
        $this->authorize('reports.view');
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->activeReport = $this->defaultReportKey();
        $this->run();
    }

    public function selectReport(string $key): void
    {
        if (! in_array($key, $this->reportKeys(), true)) {
            return;
        }

        $this->activeReport = $key;
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
            'quarter' => [
                $this->dateFrom = now()->startOfQuarter()->toDateString(),
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

        if ($this->activeReport === '' || $this->dateFrom === '' || $this->dateTo === '') {
            return;
        }

        if ($this->dateTo < $this->dateFrom) {
            $this->errorMessage = 'The end date must be on or after the start date.';
            $this->rows = [];
            $this->summary = [];
            $this->columns = [];
            $this->breakdowns = [];
            $this->charts = [];

            return;
        }

        $result = app(ReportService::class)->generate(
            $this->activeReport,
            Carbon::parse($this->dateFrom)->startOfDay(),
            Carbon::parse($this->dateTo)->endOfDay(),
        );

        $this->reportTitle = $result['title'];
        $this->columns = $result['columns'];
        $this->rows = $result['rows'];
        $this->summary = $result['summary'];
        $this->breakdowns = $result['breakdowns'] ?? [];
        $this->charts = $result['charts'] ?? [];
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
        $breakdowns = $this->breakdowns;
        $title = $this->reportTitle;
        $dateFrom = $this->dateFrom;
        $dateTo = $this->dateTo;

        return response()->streamDownload(function () use ($columns, $rows, $summary, $breakdowns, $title, $dateFrom, $dateTo) {
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

            foreach ($breakdowns as $breakdown) {
                fputcsv($handle, [$breakdown['title'] ?? 'Breakdown']);
                fputcsv($handle, $breakdown['columns'] ?? []);
                foreach ($breakdown['rows'] ?? [] as $row) {
                    fputcsv($handle, $row);
                }
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

    public function catalogEntry(): array
    {
        return app(ReportService::class)->catalog()[$this->activeReport] ?? [];
    }
}
