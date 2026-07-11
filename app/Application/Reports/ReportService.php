<?php

namespace App\Application\Reports;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\Payment;
use App\Domain\Sales\Models\Quote;
use App\Domain\Shared\Enums\InvoiceStatus;
use App\Domain\Shared\Enums\QuoteStatus;
use App\Domain\Targets\Models\Target;
use App\Domain\Tasks\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @return array<string, array{key: string, name: string, description: string, category: string}>
     */
    public function catalog(): array
    {
        return [
            'sales_pipeline' => [
                'key' => 'sales_pipeline',
                'name' => 'Sales pipeline',
                'description' => 'Quotes and their status for the selected dates.',
                'category' => 'Sales',
            ],
            'invoice_outstanding' => [
                'key' => 'invoice_outstanding',
                'name' => 'Outstanding invoices',
                'description' => 'Invoices that still have money unpaid.',
                'category' => 'Finance',
            ],
            'payments_received' => [
                'key' => 'payments_received',
                'name' => 'Payments received',
                'description' => 'Money collected during the selected dates.',
                'category' => 'Finance',
            ],
            'revenue_by_customer' => [
                'key' => 'revenue_by_customer',
                'name' => 'Revenue by customer',
                'description' => 'How much each customer was billed.',
                'category' => 'Finance',
            ],
            'new_customers' => [
                'key' => 'new_customers',
                'name' => 'New customers',
                'description' => 'Customers added during the selected dates.',
                'category' => 'Customers',
            ],
            'task_performance' => [
                'key' => 'task_performance',
                'name' => 'Task performance',
                'description' => 'Tasks created and how many were completed.',
                'category' => 'Operations',
            ],
            'appointment_summary' => [
                'key' => 'appointment_summary',
                'name' => 'Appointment summary',
                'description' => 'Appointments scheduled in the selected dates.',
                'category' => 'Operations',
            ],
            'target_progress' => [
                'key' => 'target_progress',
                'name' => 'Target progress',
                'description' => 'How close targets are to being met.',
                'category' => 'Performance',
            ],
        ];
    }

    /**
     * @return array{title: string, columns: array<int, string>, rows: array<int, array<int, string|int|float>>, summary: array<string, string|int|float>}
     */
    public function generate(string $key, Carbon $from, Carbon $to): array
    {
        return match ($key) {
            'sales_pipeline' => $this->salesPipeline($from, $to),
            'invoice_outstanding' => $this->invoiceOutstanding(),
            'payments_received' => $this->paymentsReceived($from, $to),
            'revenue_by_customer' => $this->revenueByCustomer($from, $to),
            'new_customers' => $this->newCustomers($from, $to),
            'task_performance' => $this->taskPerformance($from, $to),
            'appointment_summary' => $this->appointmentSummary($from, $to),
            'target_progress' => $this->targetProgress(),
            default => throw new \InvalidArgumentException("Unknown report [{$key}]"),
        };
    }

    private function salesPipeline(Carbon $from, Carbon $to): array
    {
        $quotes = Quote::with('customer')
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('issue_date')
            ->get();

        $accepted = $quotes->where('status', QuoteStatus::Accepted)->count();
        $sentOrBeyond = $quotes->whereIn('status', [
            QuoteStatus::Sent,
            QuoteStatus::Accepted,
            QuoteStatus::Rejected,
        ])->count();

        return [
            'title' => 'Sales pipeline',
            'columns' => ['Quote #', 'Customer', 'Status', 'Issue date', 'Valid until', 'Total'],
            'rows' => $quotes->map(fn (Quote $quote) => [
                $quote->quote_number,
                $quote->customer?->fullName() ?? '—',
                $quote->status->label(),
                $quote->issue_date?->format('Y-m-d') ?? '—',
                $quote->valid_until?->format('Y-m-d') ?? '—',
                number_format((float) $quote->total_amount, 2),
            ])->all(),
            'summary' => [
                'Quotes' => $quotes->count(),
                'Accepted' => $accepted,
                'Conversion rate' => $sentOrBeyond > 0
                    ? round(($accepted / $sentOrBeyond) * 100, 1).'%'
                    : '0%',
                'Pipeline value' => '$'.number_format((float) $quotes->sum('total_amount'), 2),
            ],
        ];
    }

    private function invoiceOutstanding(): array
    {
        $invoices = Invoice::with('customer')
            ->whereIn('status', [
                InvoiceStatus::Sent,
                InvoiceStatus::Partial,
                InvoiceStatus::Overdue,
            ])
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->outstandingBalance() > 0);

        return [
            'title' => 'Outstanding invoices',
            'columns' => ['Invoice #', 'Customer', 'Status', 'Issue date', 'Due date', 'Total', 'Paid', 'Outstanding'],
            'rows' => $invoices->map(fn (Invoice $invoice) => [
                $invoice->invoice_number,
                $invoice->customer?->fullName() ?? '—',
                $invoice->status->label(),
                $invoice->issue_date?->format('Y-m-d') ?? '—',
                $invoice->due_date?->format('Y-m-d') ?? '—',
                number_format((float) $invoice->total_amount, 2),
                number_format((float) $invoice->amount_paid, 2),
                number_format($invoice->outstandingBalance(), 2),
            ])->values()->all(),
            'summary' => [
                'Open invoices' => $invoices->count(),
                'Total billed' => '$'.number_format((float) $invoices->sum('total_amount'), 2),
                'Total outstanding' => '$'.number_format((float) $invoices->sum(fn (Invoice $i) => $i->outstandingBalance()), 2),
            ],
        ];
    }

    private function paymentsReceived(Carbon $from, Carbon $to): array
    {
        $payments = Payment::with(['invoice', 'recorder'])
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('payment_date')
            ->get();

        return [
            'title' => 'Payments received',
            'columns' => ['Date', 'Invoice #', 'Method', 'Reference', 'Recorded by', 'Amount'],
            'rows' => $payments->map(fn (Payment $payment) => [
                $payment->payment_date?->format('Y-m-d') ?? '—',
                $payment->invoice?->invoice_number ?? '—',
                ucfirst(str_replace('_', ' ', $payment->payment_method)),
                $payment->reference ?: '—',
                $payment->recorder?->name ?? '—',
                number_format((float) $payment->amount, 2),
            ])->all(),
            'summary' => [
                'Payments' => $payments->count(),
                'Total received' => '$'.number_format((float) $payments->sum('amount'), 2),
                'Cash' => '$'.number_format((float) $payments->where('payment_method', 'cash')->sum('amount'), 2),
                'Card / transfer' => '$'.number_format((float) $payments->whereIn('payment_method', ['credit_card', 'bank_transfer'])->sum('amount'), 2),
            ],
        ];
    }

    private function revenueByCustomer(Carbon $from, Carbon $to): array
    {
        $invoices = Invoice::with('customer')
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotIn('status', [InvoiceStatus::Cancelled])
            ->get()
            ->groupBy('customer_id');

        $rows = $invoices->map(function (Collection $group) {
            $customer = $group->first()->customer;

            return [
                $customer?->fullName() ?? '—',
                $customer?->email ?? '—',
                $group->count(),
                number_format((float) $group->sum('total_amount'), 2),
                number_format((float) $group->sum('amount_paid'), 2),
                number_format((float) $group->sum(fn (Invoice $i) => $i->outstandingBalance()), 2),
            ];
        })->sortByDesc(fn (array $row) => (float) str_replace(',', '', $row[3]))
            ->values();

        return [
            'title' => 'Revenue by customer',
            'columns' => ['Customer', 'Email', 'Invoices', 'Invoiced', 'Paid', 'Outstanding'],
            'rows' => $rows->all(),
            'summary' => [
                'Customers' => $rows->count(),
                'Total invoiced' => '$'.number_format((float) $rows->sum(fn ($row) => (float) str_replace(',', '', $row[3])), 2),
            ],
        ];
    }

    private function newCustomers(Carbon $from, Carbon $to): array
    {
        $customers = Customer::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderByDesc('created_at')
            ->get();

        return [
            'title' => 'New customers',
            'columns' => ['Name', 'Email', 'Phone', 'Company', 'VIP', 'Source', 'Added on'],
            'rows' => $customers->map(fn (Customer $customer) => [
                $customer->fullName(),
                $customer->email ?: '—',
                $customer->phone ?: '—',
                $customer->company ?: '—',
                $customer->vip_level ?: '—',
                $customer->source ?: '—',
                $customer->created_at?->format('Y-m-d') ?? '—',
            ])->all(),
            'summary' => [
                'New customers' => $customers->count(),
                'With email' => $customers->whereNotNull('email')->where('email', '!=', '')->count(),
                'VIP' => $customers->whereNotNull('vip_level')->where('vip_level', '!=', '')->count(),
            ],
        ];
    }

    private function taskPerformance(Carbon $from, Carbon $to): array
    {
        $tasks = Task::with(['assignee', 'creator'])
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderByDesc('created_at')
            ->get();

        $completed = $tasks->where('status', \App\Domain\Shared\Enums\TaskStatus::Completed)->count();

        return [
            'title' => 'Task performance',
            'columns' => ['Title', 'Status', 'Priority', 'Assignee', 'Due date', 'Created'],
            'rows' => $tasks->map(fn (Task $task) => [
                $task->title,
                $task->status->label(),
                $task->priority->label(),
                $task->assignee?->name ?? 'Unassigned',
                $task->due_date?->format('Y-m-d') ?? '—',
                $task->created_at?->format('Y-m-d') ?? '—',
            ])->all(),
            'summary' => [
                'Tasks' => $tasks->count(),
                'Completed' => $completed,
                'Completion rate' => $tasks->count() > 0
                    ? round(($completed / $tasks->count()) * 100, 1).'%'
                    : '0%',
            ],
        ];
    }

    private function appointmentSummary(Carbon $from, Carbon $to): array
    {
        $appointments = Appointment::with(['customer', 'type', 'assignee'])
            ->whereBetween('starts_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('starts_at')
            ->get();

        return [
            'title' => 'Appointment summary',
            'columns' => ['Date & time', 'Title', 'Type', 'Customer', 'Assignee', 'Status'],
            'rows' => $appointments->map(fn (Appointment $appointment) => [
                $appointment->starts_at?->format('Y-m-d H:i') ?? '—',
                $appointment->title,
                $appointment->type?->name ?? '—',
                $appointment->customer?->fullName() ?? '—',
                $appointment->assignee?->name ?? '—',
                $appointment->status->label(),
            ])->all(),
            'summary' => [
                'Appointments' => $appointments->count(),
                'Completed' => $appointments->where('status', \App\Domain\Shared\Enums\AppointmentStatus::Completed)->count(),
            ],
        ];
    }

    private function targetProgress(): array
    {
        $targets = Target::with('assignments.user')->orderByDesc('period_start')->get();

        return [
            'title' => 'Target progress',
            'columns' => ['Target', 'Metric', 'Period', 'Target value', 'Assigned', 'Actual', 'Achievement'],
            'rows' => $targets->map(fn (Target $target) => [
                $target->name,
                $target->metric->label(),
                $target->period_start?->format('Y-m-d').' → '.$target->period_end?->format('Y-m-d'),
                number_format((float) $target->target_value, 2),
                number_format($target->totalAssigned(), 2),
                number_format($target->totalActual(), 2),
                $target->achievementPercentage().'%',
            ])->all(),
            'summary' => [
                'Targets' => $targets->count(),
                'Avg achievement' => $targets->count() > 0
                    ? round($targets->avg(fn (Target $t) => $t->achievementPercentage()), 1).'%'
                    : '0%',
            ],
        ];
    }
}
