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
        return 'sales_pipeline';
    }

    protected function reportKeys(): array
    {
        return ['sales_pipeline', 'sales_by_staff'];
    }

    public function with(): array
    {
        $catalog = app(ReportService::class)->catalog();

        return [
            'pageTitle' => 'Sales reports',
            'pageDescription' => 'Pipeline conversion and sales attribution.',
            'reportTabs' => [
                $catalog['sales_pipeline'],
                $catalog['sales_by_staff'],
            ],
            'catalogEntry' => $this->catalogEntry(),
        ];
    }
}; ?>

@include('livewire.pages.reports.partials.report-page')
