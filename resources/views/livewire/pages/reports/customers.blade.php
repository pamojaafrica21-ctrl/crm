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
        return 'new_customers';
    }

    protected function reportKeys(): array
    {
        return ['new_customers'];
    }

    public function with(): array
    {
        $catalog = app(ReportService::class)->catalog();

        return [
            'pageTitle' => 'Customer reports',
            'pageDescription' => 'Acquisition and customer profile mix.',
            'reportTabs' => [
                $catalog['new_customers'],
            ],
            'catalogEntry' => $this->catalogEntry(),
        ];
    }
}; ?>

@include('livewire.pages.reports.partials.report-page')
