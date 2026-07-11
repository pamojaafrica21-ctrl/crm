<?php

namespace App\Application\Dashboard;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\Reservation;
use App\Domain\Dashboard\Models\KpiSnapshot;
use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\Quote;
use App\Domain\Shared\Enums\AppointmentStatus;
use App\Domain\Shared\Enums\QuoteStatus;
use App\Domain\Shared\Enums\TaskStatus;
use App\Domain\Tasks\Models\Task;
use Carbon\Carbon;

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
        $quotesSent = Quote::where('status', QuoteStatus::Sent)->whereBetween('issue_date', [$from, $to])->count();
        $quotesAccepted = Quote::where('status', QuoteStatus::Accepted)->whereBetween('issue_date', [$from, $to])->count();
        $conversionRate = $quotesSent > 0 ? round(($quotesAccepted / $quotesSent) * 100, 1) : 0;

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
}
