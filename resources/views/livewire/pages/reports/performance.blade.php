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
        return 'target_progress';
    }

    protected function reportKeys(): array
    {
        return ['target_progress', 'staff_performance'];
    }

    public function with(): array
    {
        $catalog = app(ReportService::class)->catalog();

        return [
            'pageTitle' => 'Performance reports',
            'pageDescription' => 'Targets and staff contribution across the CRM.',
            'reportTabs' => [
                $catalog['target_progress'],
                $catalog['staff_performance'],
            ],
            'catalogEntry' => $this->catalogEntry(),
        ];
    }
}; ?>

@include('livewire.pages.reports.partials.report-page')
