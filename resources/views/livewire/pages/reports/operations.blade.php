<?php

use App\Application\Reports\ReportService;
use App\Livewire\Concerns\ManagesReportView;
use Livewire\Volt\Component;

new class extends Component
{
    use ManagesReportView;

    public function mount(): void
    {
        $this->initializeReportView();
    }

    protected function defaultReportKey(): string
    {
        return 'task_performance';
    }

    protected function reportKeys(): array
    {
        return ['task_performance', 'appointment_summary'];
    }

    public function with(): array
    {
        $catalog = app(ReportService::class)->catalog();

        return [
            'pageTitle' => 'Operations reports',
            'pageDescription' => 'Task throughput and appointment activity.',
            'reportTabs' => [
                $catalog['task_performance'],
                $catalog['appointment_summary'],
            ],
            'catalogEntry' => $this->catalogEntry(),
        ];
    }
}; ?>

@include('livewire.pages.reports.partials.report-page')
