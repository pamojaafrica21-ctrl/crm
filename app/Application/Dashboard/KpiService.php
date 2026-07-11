<?php

namespace App\Application\Dashboard;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\Reservation;
use App\Domain\Dashboard\Models\KpiSnapshot;
use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\Payment;
use App\Domain\Sales\Models\Quote;
use App\Domain\Shared\Enums\AppointmentStatus;
use App\Domain\Shared\Enums\InvoiceStatus;
use App\Domain\Shared\Enums\QuoteStatus;
use App\Domain\Shared\Enums\TaskStatus;
use App\Domain\Tasks\Models\Task;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class KpiService
{
    public function __construct(private PropertyContext $propertyContext) {}

    public function getDashboardMetrics(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfDay();
        $propertyId = $this->propertyContext->id();

        if (! $propertyId) {
            return [];
        }

        $revenue = Invoice::whereBetween('issue_date', [$from, $to])->sum('total_amount');
        $outstanding = Invoice::whereIn('status', ['sent', 'partial', 'overdue'])
            ->selectRaw('SUM(total_amount - amount_paid) as balance')
            ->value('balance') ?? 0;

        $bookings = Reservation::whereBetween('check_in', [$from, $to])->count();
        $customers = Customer::count();
        $quotesTotal = Quote::whereBetween('issue_date', [$from, $to])->count();
        $quotesAccepted = Quote::where('status', QuoteStatus::Accepted)->whereBetween('issue_date', [$from, $to])->count();
        $conversionRate = $quotesTotal > 0 ? round(($quotesAccepted / $quotesTotal) * 100, 1) : 0;

        $tasksCompleted = Task::where('status', TaskStatus::Completed)
            ->whereBetween('completed_at', [$from, $to])->count();
        $tasksTotal = Task::whereBetween('created_at', [$from, $to])->count();
        $taskCompletionRate = $tasksTotal > 0 ? round(($tasksCompleted / $tasksTotal) * 100, 1) : 0;

        $appointments = Appointment::whereBetween('starts_at', [$from, $to])->count();
        $appointmentsCompleted = Appointment::where('status', AppointmentStatus::Completed)
            ->whereBetween('starts_at', [$from, $to])->count();

        return [
            'revenue' => (float) $revenue,
            'outstanding' => (float) $outstanding,
            'bookings' => $bookings,
            'customers' => $customers,
            'conversion_rate' => $conversionRate,
            'task_completion_rate' => $taskCompletionRate,
            'appointments' => $appointments,
            'appointments_completed' => $appointmentsCompleted,
        ];
    }

    /**
     * @return array{revenue_trend: array, quotes_by_status: array, invoices_overview: array, payments_by_method: array, tasks_by_status: array}
     */
    public function getDashboardCharts(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfDay();

        if (! $this->propertyContext->id()) {
            return [
                'revenue_trend' => ['labels' => [], 'values' => []],
                'quotes_by_status' => ['labels' => [], 'values' => []],
                'invoices_overview' => ['labels' => [], 'values' => []],
                'payments_by_method' => ['labels' => [], 'values' => []],
                'tasks_by_status' => ['labels' => [], 'values' => []],
            ];
        }

        return [
            'revenue_trend' => $this->revenueTrend($from, $to),
            'quotes_by_status' => $this->quotesByStatus($from, $to),
            'invoices_overview' => $this->invoicesOverview($from, $to),
            'payments_by_method' => $this->paymentsByMethod($from, $to),
            'tasks_by_status' => $this->tasksByStatus($from, $to),
        ];
    }

    public function snapshotDaily(int $propertyId): void
    {
        $this->propertyContext->set($propertyId);
        $metrics = $this->getDashboardMetrics(now()->startOfDay(), now()->endOfDay());

        foreach ($metrics as $key => $value) {
            KpiSnapshot::updateOrCreate(
                [
                    'property_id' => $propertyId,
                    'metric_key' => $key,
                    'period_date' => now()->toDateString(),
                    'period_type' => 'daily',
                ],
                ['value' => $value]
            );
        }
    }

    private function revenueTrend(Carbon $from, Carbon $to): array
    {
        $days = $from->diffInDays($to);
        $groupByWeek = $days > 45;

        $invoices = Invoice::query()
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->get(['issue_date', 'total_amount']);

        if ($groupByWeek) {
            $buckets = [];
            foreach ($invoices as $invoice) {
                $weekStart = $invoice->issue_date->copy()->startOfWeek()->format('Y-m-d');
                $buckets[$weekStart] = ($buckets[$weekStart] ?? 0) + (float) $invoice->total_amount;
            }

            $labels = [];
            $values = [];
            $cursor = $from->copy()->startOfWeek();
            while ($cursor->lte($to)) {
                $key = $cursor->format('Y-m-d');
                $labels[] = $cursor->format('M j');
                $values[] = round($buckets[$key] ?? 0, 2);
                $cursor->addWeek();
            }

            return ['labels' => $labels, 'values' => $values];
        }

        $byDate = $invoices->groupBy(fn ($invoice) => $invoice->issue_date->format('Y-m-d'))
            ->map(fn ($group) => round((float) $group->sum('total_amount'), 2));

        $labels = [];
        $values = [];
        foreach (CarbonPeriod::create($from->toDateString(), $to->toDateString()) as $date) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $values[] = $byDate[$key] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function quotesByStatus(Carbon $from, Carbon $to): array
    {
        $counts = Quote::query()
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $labels = [];
        $values = [];

        foreach (QuoteStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);
            if ($count === 0) {
                continue;
            }
            $labels[] = $status->label();
            $values[] = $count;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function invoicesOverview(Carbon $from, Carbon $to): array
    {
        $invoices = Invoice::query()
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->where('status', '!=', InvoiceStatus::Cancelled)
            ->get(['total_amount', 'amount_paid']);

        $paid = (float) $invoices->sum('amount_paid');
        $outstanding = (float) $invoices->sum(fn (Invoice $invoice) => max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid));

        return [
            'labels' => ['Paid', 'Outstanding'],
            'values' => [round($paid, 2), round($outstanding, 2)],
        ];
    }

    private function paymentsByMethod(Carbon $from, Carbon $to): array
    {
        $payments = Payment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        $labels = [];
        $values = [];

        foreach ($payments as $method => $total) {
            $labels[] = ucfirst(str_replace('_', ' ', (string) $method));
            $values[] = round((float) $total, 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function tasksByStatus(Carbon $from, Carbon $to): array
    {
        $counts = Task::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $labels = [];
        $values = [];

        foreach (TaskStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);
            if ($count === 0) {
                continue;
            }
            $labels[] = $status->label();
            $values[] = $count;
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
