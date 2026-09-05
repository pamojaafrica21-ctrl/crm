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
        return 'invoice_outstanding';
    }

    protected function reportKeys(): array
    {
        return ['invoice_outstanding', 'payments_received', 'revenue_by_customer'];
    }

    public function with(): array
    {
        $catalog = app(ReportService::class)->catalog();

        return [
            'pageTitle' => 'Finance reports',
            'pageDescription' => 'Receivables, collections, and customer revenue.',
            'reportTabs' => [
                $catalog['invoice_outstanding'],
                $catalog['payments_received'],
                $catalog['revenue_by_customer'],
            ],
            'catalogEntry' => $this->catalogEntry(),
        ];
    }
}; ?>

@include('livewire.pages.reports.partials.report-page')
