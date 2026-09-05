<?php

namespace App\Application\Reports;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\Payment;
use App\Domain\Sales\Models\Quote;
use App\Domain\Shared\Enums\AppointmentStatus;
use App\Domain\Shared\Enums\InvoiceStatus;
use App\Domain\Shared\Enums\QuoteStatus;
use App\Domain\Shared\Enums\TaskStatus;
use App\Domain\Targets\Models\Target;
use App\Domain\Tasks\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @return array<string, array{key: string, name: string, description: string, category: string, route: string}>
     */
    public function catalog(): array
    {
        return [
            'sales_pipeline' => [
                'key' => 'sales_pipeline',
                'name' => 'Sales pipeline',
                'description' => 'Quotes by status, owner, and conversion for the selected dates.',
                'category' => 'Sales',
                'route' => 'reports.sales',
            ],
            'sales_by_staff' => [
                'key' => 'sales_by_staff',
                'name' => 'Sales by staff',
                'description' => 'Quote volume and accepted value grouped by the staff member who created them.',
                'category' => 'Sales',
                'route' => 'reports.sales',
            ],
            'invoice_outstanding' => [
                'key' => 'invoice_outstanding',
                'name' => 'Outstanding invoices',
                'description' => 'Open invoices with aging, due dates, and unpaid balances.',
                'category' => 'Finance',
                'route' => 'reports.finance',
            ],
            'payments_received' => [
                'key' => 'payments_received',
                'name' => 'Payments received',
                'description' => 'Collections by method, recorder, and day.',
                'category' => 'Finance',
                'route' => 'reports.finance',
            ],
            'revenue_by_customer' => [
                'key' => 'revenue_by_customer',
                'name' => 'Revenue by customer',
                'description' => 'Invoiced, paid, and outstanding amounts per customer.',
                'category' => 'Finance',
                'route' => 'reports.finance',
            ],
            'new_customers' => [
                'key' => 'new_customers',
                'name' => 'New customers',
                'description' => 'Customers added in the period, with source and VIP breakdowns.',
                'category' => 'Customers',
                'route' => 'reports.customers',
            ],
            'task_performance' => [
                'key' => 'task_performance',
                'name' => 'Task performance',
                'description' => 'Task completion, overdue work, and load by assignee and department.',
                'category' => 'Operations',
                'route' => 'reports.operations',
            ],
            'appointment_summary' => [
                'key' => 'appointment_summary',
                'name' => 'Appointment summary',
                'description' => 'Scheduled appointments by type, status, assignee, and department.',
                'category' => 'Operations',
                'route' => 'reports.operations',
            ],
            'target_progress' => [
                'key' => 'target_progress',
                'name' => 'Target progress',
                'description' => 'Targets overlapping the selected dates, with achievement and staff assignments.',
                'category' => 'Performance',
                'route' => 'reports.performance',
            ],
            'staff_performance' => [
                'key' => 'staff_performance',
                'name' => 'Staff performance',
                'description' => 'Quotes, invoices, tasks, and appointments attributed to each staff member.',
                'category' => 'Performance',
                'route' => 'reports.performance',
            ],
        ];
    }

    /**
     * @return array<int, array{key: string, name: string, description: string, reports: array<int, array>}>
     */
    public function categories(): array
    {
        $catalog = collect($this->catalog());

        return [
            [
                'key' => 'sales',
                'name' => 'Sales',
                'description' => 'Pipeline health, conversion, and sales attribution by staff.',
                'route' => 'reports.sales',
                'reports' => $catalog->where('category', 'Sales')->values()->all(),
            ],
            [
                'key' => 'finance',
                'name' => 'Finance',
                'description' => 'Outstanding balances, collections, and revenue by customer.',
                'route' => 'reports.finance',
                'reports' => $catalog->where('category', 'Finance')->values()->all(),
            ],
            [
                'key' => 'customers',
                'name' => 'Customers',
                'description' => 'New customer acquisition and profile mix.',
                'route' => 'reports.customers',
                'reports' => $catalog->where('category', 'Customers')->values()->all(),
            ],
            [
                'key' => 'operations',
                'name' => 'Operations',
                'description' => 'Task throughput and appointment activity across teams.',
                'route' => 'reports.operations',
                'reports' => $catalog->where('category', 'Operations')->values()->all(),
            ],
            [
                'key' => 'performance',
                'name' => 'Performance',
                'description' => 'Targets and individual staff contribution.',
                'route' => 'reports.performance',
                'reports' => $catalog->where('category', 'Performance')->values()->all(),
            ],
        ];
    }

    /**
     * @return array{
     *   title: string,
     *   columns: array<int, string>,
     *   rows: array<int, array<int, string|int|float>>,
     *   summary: array<string, string|int|float>,
     *   breakdowns?: array<int, array{title: string, columns: array<int, string>, rows: array<int, array>}>,
     *   charts?: array<int, array{title: string, type: string, labels: array, values: array}>
     * }
     */
    public function generate(string $key, Carbon $from, Carbon $to): array
    {
        return match ($key) {
            'sales_pipeline' => $this->salesPipeline($from, $to),
            'sales_by_staff' => $this->salesByStaff($from, $to),
            'invoice_outstanding' => $this->invoiceOutstanding($from, $to),
            'payments_received' => $this->paymentsReceived($from, $to),
            'revenue_by_customer' => $this->revenueByCustomer($from, $to),
            'new_customers' => $this->newCustomers($from, $to),
            'task_performance' => $this->taskPerformance($from, $to),
            'appointment_summary' => $this->appointmentSummary($from, $to),
            'target_progress' => $this->targetProgress($from, $to),
            'staff_performance' => $this->staffPerformance($from, $to),
            default => throw new \InvalidArgumentException("Unknown report [{$key}]"),
        };
    }

    private function salesPipeline(Carbon $from, Carbon $to): array
    {
        $quotes = Quote::with(['customer', 'creator'])
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('issue_date')
            ->get();

        $accepted = $quotes->where('status', QuoteStatus::Accepted);
        $rejected = $quotes->where('status', QuoteStatus::Rejected);
        $sentOrBeyond = $quotes->whereIn('status', [
            QuoteStatus::Sent,
            QuoteStatus::Accepted,
            QuoteStatus::Rejected,
            QuoteStatus::Expired,
        ]);

        $byStatus = $quotes->groupBy(fn (Quote $q) => $q->status->label())
            ->map(fn (Collection $group) => [
                $group->first()->status->label(),
                $group->count(),
                number_format((float) $group->sum('total_amount'), 2),
            ])->values();

        $statusChart = $this->chartFromGroups($quotes->groupBy(fn (Quote $q) => $q->status->label()));

        return [
            'title' => 'Sales pipeline',
            'columns' => ['Quote #', 'Customer', 'Owner', 'Status', 'Issue date', 'Valid until', 'Days open', 'Total'],
            'rows' => $quotes->map(function (Quote $quote) {
                $daysOpen = $quote->issue_date
                    ? $quote->issue_date->diffInDays($quote->valid_until ?? now())
                    : null;

                return [
                    $quote->quote_number,
                    $quote->customer?->fullName() ?? '—',
                    $quote->creator?->name ?? '—',
                    $quote->status->label(),
                    $quote->issue_date?->format('Y-m-d') ?? '—',
                    $quote->valid_until?->format('Y-m-d') ?? '—',
                    $daysOpen ?? '—',
                    number_format((float) $quote->total_amount, 2),
                ];
            })->all(),
            'summary' => [
                'Quotes' => $quotes->count(),
                'Accepted' => $accepted->count(),
                'Rejected' => $rejected->count(),
                'Conversion rate' => $sentOrBeyond->count() > 0
                    ? round(($accepted->count() / $sentOrBeyond->count()) * 100, 1).'%'
                    : '0%',
                'Accepted value' => '$'.number_format((float) $accepted->sum('total_amount'), 2),
                'Pipeline value' => '$'.number_format((float) $quotes->sum('total_amount'), 2),
            ],
            'breakdowns' => [
                [
                    'title' => 'By status',
                    'columns' => ['Status', 'Quotes', 'Value'],
                    'rows' => $byStatus->all(),
                ],
            ],
            'charts' => [
                [
                    'title' => 'Quotes by status',
                    'type' => 'doughnut',
                    'labels' => $statusChart['labels'],
                    'values' => $statusChart['values'],
                ],
            ],
        ];
    }

    private function salesByStaff(Carbon $from, Carbon $to): array
    {
        $quotes = Quote::with('creator')
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy('created_by');

        $rows = $quotes->map(function (Collection $group) {
            $accepted = $group->where('status', QuoteStatus::Accepted);
            $created = $group->count();

            return [
                $group->first()->creator?->name ?? 'Unknown',
                $created,
                $accepted->count(),
                $created > 0 ? round(($accepted->count() / $created) * 100, 1).'%' : '0%',
                number_format((float) $group->sum('total_amount'), 2),
                number_format((float) $accepted->sum('total_amount'), 2),
            ];
        })->sortByDesc(fn (array $row) => (float) str_replace(',', '', $row[4]))
            ->values();

        return [
            'title' => 'Sales by staff',
            'columns' => ['Staff', 'Quotes', 'Accepted', 'Win rate', 'Pipeline value', 'Accepted value'],
            'rows' => $rows->all(),
            'summary' => [
                'Staff with quotes' => $rows->count(),
                'Total quotes' => $quotes->flatten()->count(),
                'Total accepted value' => '$'.number_format((float) $rows->sum(fn ($row) => (float) str_replace(',', '', $row[5])), 2),
            ],
            'breakdowns' => [],
            'charts' => [
                [
                    'title' => 'Accepted value by staff',
                    'type' => 'bar',
                    'labels' => $rows->pluck(0)->all(),
                    'values' => $rows->map(fn ($row) => (float) str_replace(',', '', $row[5]))->all(),
                ],
            ],
        ];
    }

    private function invoiceOutstanding(Carbon $from, Carbon $to): array
    {
        $invoices = Invoice::with(['customer', 'creator'])
            ->whereIn('status', [
                InvoiceStatus::Sent,
                InvoiceStatus::Partial,
                InvoiceStatus::Overdue,
            ])
            ->whereDate('issue_date', '<=', $to->toDateString())
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('due_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhere('status', InvoiceStatus::Overdue);
            })
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->outstandingBalance() > 0)
            ->values();

        $agingBuckets = [
            'Current' => 0.0,
            '1–30 days' => 0.0,
            '31–60 days' => 0.0,
            '61–90 days' => 0.0,
            '90+ days' => 0.0,
        ];

        $rows = $invoices->map(function (Invoice $invoice) use (&$agingBuckets) {
            $outstanding = $invoice->outstandingBalance();
            $daysOverdue = $invoice->due_date
                ? max(0, $invoice->due_date->diffInDays(now(), false))
                : 0;

            if ($daysOverdue <= 0) {
                $agingBuckets['Current'] += $outstanding;
                $agingLabel = 'Current';
            } elseif ($daysOverdue <= 30) {
                $agingBuckets['1–30 days'] += $outstanding;
                $agingLabel = '1–30 days';
            } elseif ($daysOverdue <= 60) {
                $agingBuckets['31–60 days'] += $outstanding;
                $agingLabel = '31–60 days';
            } elseif ($daysOverdue <= 90) {
                $agingBuckets['61–90 days'] += $outstanding;
                $agingLabel = '61–90 days';
            } else {
                $agingBuckets['90+ days'] += $outstanding;
                $agingLabel = '90+ days';
            }

            return [
                $invoice->invoice_number,
                $invoice->customer?->fullName() ?? '—',
                $invoice->creator?->name ?? '—',
                $invoice->status->label(),
                $invoice->issue_date?->format('Y-m-d') ?? '—',
                $invoice->due_date?->format('Y-m-d') ?? '—',
                $agingLabel,
                $daysOverdue > 0 ? $daysOverdue : '—',
                number_format((float) $invoice->total_amount, 2),
                number_format((float) $invoice->amount_paid, 2),
                number_format($outstanding, 2),
            ];
        });

        return [
            'title' => 'Outstanding invoices',
            'columns' => ['Invoice #', 'Customer', 'Owner', 'Status', 'Issue date', 'Due date', 'Aging', 'Days overdue', 'Total', 'Paid', 'Outstanding'],
            'rows' => $rows->all(),
            'summary' => [
                'Open invoices' => $invoices->count(),
                'Total billed' => '$'.number_format((float) $invoices->sum('total_amount'), 2),
                'Total outstanding' => '$'.number_format((float) $invoices->sum(fn (Invoice $i) => $i->outstandingBalance()), 2),
                'Overdue invoices' => $invoices->where('status', InvoiceStatus::Overdue)->count(),
            ],
            'breakdowns' => [
                [
                    'title' => 'Aging buckets',
                    'columns' => ['Bucket', 'Outstanding'],
                    'rows' => collect($agingBuckets)->map(fn ($amount, $label) => [
                        $label,
                        number_format($amount, 2),
                    ])->values()->all(),
                ],
            ],
            'charts' => [
                [
                    'title' => 'Outstanding by aging',
                    'type' => 'bar',
                    'labels' => array_keys($agingBuckets),
                    'values' => array_map(fn ($v) => round($v, 2), array_values($agingBuckets)),
                ],
            ],
        ];
    }

    private function paymentsReceived(Carbon $from, Carbon $to): array
    {
        $payments = Payment::with(['invoice.customer', 'recorder'])
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('payment_date')
            ->get();

        $byMethod = $payments->groupBy('payment_method')->map(fn (Collection $group, $method) => [
            ucfirst(str_replace('_', ' ', (string) $method)),
            $group->count(),
            number_format((float) $group->sum('amount'), 2),
        ])->values();

        $byRecorder = $payments->groupBy('recorded_by')->map(fn (Collection $group) => [
            $group->first()->recorder?->name ?? 'Unknown',
            $group->count(),
            number_format((float) $group->sum('amount'), 2),
        ])->sortByDesc(fn ($row) => (float) str_replace(',', '', $row[2]))->values();

        $trend = $this->sumByDay(
            $payments,
            fn (Payment $payment) => $payment->payment_date?->format('Y-m-d'),
            fn (Payment $payment) => (float) $payment->amount,
            $from,
            $to
        );

        return [
            'title' => 'Payments received',
            'columns' => ['Date', 'Invoice #', 'Customer', 'Method', 'Reference', 'Recorded by', 'Amount'],
            'rows' => $payments->map(fn (Payment $payment) => [
                $payment->payment_date?->format('Y-m-d') ?? '—',
                $payment->invoice?->invoice_number ?? '—',
                $payment->invoice?->customer?->fullName() ?? '—',
                ucfirst(str_replace('_', ' ', $payment->payment_method)),
                $payment->reference ?: '—',
                $payment->recorder?->name ?? '—',
                number_format((float) $payment->amount, 2),
            ])->all(),
            'summary' => [
                'Payments' => $payments->count(),
                'Total received' => '$'.number_format((float) $payments->sum('amount'), 2),
                'Average payment' => '$'.number_format($payments->count() > 0 ? (float) $payments->avg('amount') : 0, 2),
                'Cash' => '$'.number_format((float) $payments->where('payment_method', 'cash')->sum('amount'), 2),
            ],
            'breakdowns' => [
                [
                    'title' => 'By payment method',
                    'columns' => ['Method', 'Count', 'Amount'],
                    'rows' => $byMethod->all(),
                ],
                [
                    'title' => 'By recorded staff',
                    'columns' => ['Staff', 'Count', 'Amount'],
                    'rows' => $byRecorder->all(),
                ],
            ],
            'charts' => [
                [
                    'title' => 'Collections trend',
                    'type' => 'line',
                    'labels' => $trend['labels'],
                    'values' => $trend['values'],
                ],
                [
                    'title' => 'By method',
                    'type' => 'doughnut',
                    'labels' => $byMethod->pluck(0)->all(),
                    'values' => $byMethod->map(fn ($row) => (float) str_replace(',', '', $row[2]))->all(),
                ],
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
            $invoiced = (float) $group->sum('total_amount');
            $paid = (float) $group->sum('amount_paid');
            $outstanding = (float) $group->sum(fn (Invoice $i) => $i->outstandingBalance());

            return [
                $customer?->fullName() ?? '—',
                $customer?->email ?? '—',
                $customer?->company ?: '—',
                $group->count(),
                number_format($invoiced, 2),
                number_format($paid, 2),
                number_format($outstanding, 2),
                $invoiced > 0 ? round(($paid / $invoiced) * 100, 1).'%' : '0%',
            ];
        })->sortByDesc(fn (array $row) => (float) str_replace(',', '', $row[4]))
            ->values();

        $top = $rows->take(10);

        return [
            'title' => 'Revenue by customer',
            'columns' => ['Customer', 'Email', 'Company', 'Invoices', 'Invoiced', 'Paid', 'Outstanding', 'Collection rate'],
            'rows' => $rows->all(),
            'summary' => [
                'Customers' => $rows->count(),
                'Total invoiced' => '$'.number_format((float) $rows->sum(fn ($row) => (float) str_replace(',', '', $row[4])), 2),
                'Total paid' => '$'.number_format((float) $rows->sum(fn ($row) => (float) str_replace(',', '', $row[5])), 2),
                'Total outstanding' => '$'.number_format((float) $rows->sum(fn ($row) => (float) str_replace(',', '', $row[6])), 2),
            ],
            'breakdowns' => [
                [
                    'title' => 'Top 10 customers by invoiced amount',
                    'columns' => ['Customer', 'Invoiced', 'Paid'],
                    'rows' => $top->map(fn ($row) => [$row[0], $row[4], $row[5]])->all(),
                ],
            ],
            'charts' => [
                [
                    'title' => 'Top customers (invoiced)',
                    'type' => 'bar',
                    'labels' => $top->pluck(0)->all(),
                    'values' => $top->map(fn ($row) => (float) str_replace(',', '', $row[4]))->all(),
                ],
            ],
        ];
    }

    private function newCustomers(Carbon $from, Carbon $to): array
    {
        $customers = Customer::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderByDesc('created_at')
            ->get();

        $bySource = $customers->groupBy(fn (Customer $c) => $c->source ?: 'Unknown')
            ->map(fn (Collection $group, $source) => [$source, $group->count()])
            ->sortByDesc(fn ($row) => $row[1])
            ->values();

        $trend = $this->countByDay(
            $customers,
            fn (Customer $customer) => $customer->created_at?->format('Y-m-d'),
            $from,
            $to
        );

        return [
            'title' => 'New customers',
            'columns' => ['Name', 'Email', 'Phone', 'Company', 'VIP', 'Source', 'Nationality', 'Added on'],
            'rows' => $customers->map(fn (Customer $customer) => [
                $customer->fullName(),
                $customer->email ?: '—',
                $customer->phone ?: '—',
                $customer->company ?: '—',
                $customer->vip_level ?: '—',
                $customer->source ?: '—',
                $customer->nationality ?: '—',
                $customer->created_at?->format('Y-m-d') ?? '—',
            ])->all(),
            'summary' => [
                'New customers' => $customers->count(),
                'With email' => $customers->whereNotNull('email')->where('email', '!=', '')->count(),
                'With phone' => $customers->whereNotNull('phone')->where('phone', '!=', '')->count(),
                'VIP' => $customers->whereNotNull('vip_level')->where('vip_level', '!=', '')->count(),
            ],
            'breakdowns' => [
                [
                    'title' => 'By source',
                    'columns' => ['Source', 'Customers'],
                    'rows' => $bySource->all(),
                ],
            ],
            'charts' => [
                [
                    'title' => 'New customers over time',
                    'type' => 'line',
                    'labels' => $trend['labels'],
                    'values' => $trend['values'],
                ],
                [
                    'title' => 'By source',
                    'type' => 'doughnut',
                    'labels' => $bySource->pluck(0)->all(),
                    'values' => $bySource->pluck(1)->all(),
                ],
            ],
        ];
    }

    private function taskPerformance(Carbon $from, Carbon $to): array
    {
        $tasks = Task::with(['assignee', 'assignees', 'departments', 'creator'])
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderByDesc('created_at')
            ->get();

        $completed = $tasks->where('status', TaskStatus::Completed);
        $overdue = $tasks->filter(fn (Task $task) => $task->due_date
            && $task->due_date->lt(now()->startOfDay())
            && $task->status !== TaskStatus::Completed
            && $task->status !== TaskStatus::Cancelled);

        $byAssignee = $tasks->groupBy(fn (Task $task) => $task->assignee?->name ?? 'Unassigned')
            ->map(function (Collection $group, $name) {
                $done = $group->where('status', TaskStatus::Completed)->count();

                return [
                    $name,
                    $group->count(),
                    $done,
                    $group->count() > 0 ? round(($done / $group->count()) * 100, 1).'%' : '0%',
                ];
            })->sortByDesc(fn ($row) => $row[1])->values();

        $byDepartment = $tasks->flatMap(fn (Task $task) => $task->departments->map(fn ($dept) => [
            'department' => $dept->name,
            'task' => $task,
        ]))->groupBy('department')->map(function (Collection $group, $name) {
            $taskGroup = $group->pluck('task');
            $done = $taskGroup->where('status', TaskStatus::Completed)->count();

            return [$name, $taskGroup->count(), $done];
        })->values();

        $statusChart = $this->chartFromGroups($tasks->groupBy(fn (Task $t) => $t->status->label()));

        return [
            'title' => 'Task performance',
            'columns' => ['Title', 'Status', 'Priority', 'Assignee', 'Departments', 'Due date', 'Completed', 'Created'],
            'rows' => $tasks->map(fn (Task $task) => [
                $task->title,
                $task->status->label(),
                $task->priority->label(),
                $task->assignees->isNotEmpty()
                    ? $task->assignees->pluck('name')->join(', ')
                    : ($task->assignee?->name ?? 'Unassigned'),
                $task->departments->isNotEmpty() ? $task->departments->pluck('name')->join(', ') : '—',
                $task->due_date?->format('Y-m-d') ?? '—',
                $task->completed_at?->format('Y-m-d') ?? '—',
                $task->created_at?->format('Y-m-d') ?? '—',
            ])->all(),
            'summary' => [
                'Tasks' => $tasks->count(),
                'Completed' => $completed->count(),
                'Overdue' => $overdue->count(),
                'Completion rate' => $tasks->count() > 0
                    ? round(($completed->count() / $tasks->count()) * 100, 1).'%'
                    : '0%',
            ],
            'breakdowns' => [
                [
                    'title' => 'By assignee',
                    'columns' => ['Assignee', 'Tasks', 'Completed', 'Rate'],
                    'rows' => $byAssignee->all(),
                ],
                [
                    'title' => 'By department',
                    'columns' => ['Department', 'Tasks', 'Completed'],
                    'rows' => $byDepartment->all(),
                ],
            ],
            'charts' => [
                [
                    'title' => 'Tasks by status',
                    'type' => 'doughnut',
                    'labels' => $statusChart['labels'],
                    'values' => $statusChart['values'],
                ],
            ],
        ];
    }

    private function appointmentSummary(Carbon $from, Carbon $to): array
    {
        $appointments = Appointment::with(['customer', 'type', 'assignee', 'departments'])
            ->whereBetween('starts_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('starts_at')
            ->get();

        $byType = $appointments->groupBy(fn (Appointment $a) => $a->type?->name ?? 'Unknown')
            ->map(fn (Collection $group, $name) => [
                $name,
                $group->count(),
                $group->where('status', AppointmentStatus::Completed)->count(),
                $group->where('status', AppointmentStatus::NoShow)->count(),
            ])->values();

        $byStatus = $this->chartFromGroups($appointments->groupBy(fn (Appointment $a) => $a->status->label()));

        $byDepartment = $appointments->flatMap(fn (Appointment $appointment) => $appointment->departments->map(fn ($dept) => [
            'department' => $dept->name,
            'appointment' => $appointment,
        ]))->groupBy('department')->map(fn (Collection $group, $name) => [
            $name,
            $group->count(),
        ])->values();

        return [
            'title' => 'Appointment summary',
            'columns' => ['Date & time', 'Title', 'Type', 'Customer', 'Assignee', 'Departments', 'Location', 'Status'],
            'rows' => $appointments->map(fn (Appointment $appointment) => [
                $appointment->starts_at?->format('Y-m-d H:i') ?? '—',
                $appointment->title,
                $appointment->type?->name ?? '—',
                $appointment->customer?->fullName() ?? '—',
                $appointment->assignee?->name ?? '—',
                $appointment->departments->isNotEmpty() ? $appointment->departments->pluck('name')->join(', ') : '—',
                $appointment->location ?: '—',
                $appointment->status->label(),
            ])->all(),
            'summary' => [
                'Appointments' => $appointments->count(),
                'Completed' => $appointments->where('status', AppointmentStatus::Completed)->count(),
                'Cancelled' => $appointments->where('status', AppointmentStatus::Cancelled)->count(),
                'No-shows' => $appointments->where('status', AppointmentStatus::NoShow)->count(),
            ],
            'breakdowns' => [
                [
                    'title' => 'By type',
                    'columns' => ['Type', 'Total', 'Completed', 'No-shows'],
                    'rows' => $byType->all(),
                ],
                [
                    'title' => 'By department',
                    'columns' => ['Department', 'Appointments'],
                    'rows' => $byDepartment->all(),
                ],
            ],
            'charts' => [
                [
                    'title' => 'Appointments by status',
                    'type' => 'doughnut',
                    'labels' => $byStatus['labels'],
                    'values' => $byStatus['values'],
                ],
            ],
        ];
    }

    private function targetProgress(Carbon $from, Carbon $to): array
    {
        $targets = Target::with(['assignments.user', 'departments', 'creator'])
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('period_start', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('period_end', [$from->toDateString(), $to->toDateString()])
                    ->orWhere(function ($overlap) use ($from, $to) {
                        $overlap->whereDate('period_start', '<=', $to->toDateString())
                            ->whereDate('period_end', '>=', $from->toDateString());
                    });
            })
            ->orderByDesc('period_start')
            ->get();

        $assignmentRows = $targets->flatMap(function (Target $target) {
            return $target->assignments->map(fn ($assignment) => [
                $target->name,
                $assignment->user?->name ?? '—',
                number_format((float) $assignment->assigned_value, 2),
                number_format((float) $assignment->actual_value, 2),
                $assignment->achievementPercentage().'%',
            ]);
        })->values();

        return [
            'title' => 'Target progress',
            'columns' => ['Target', 'Metric', 'Period', 'Departments', 'Target value', 'Assigned', 'Actual', 'Achievement'],
            'rows' => $targets->map(fn (Target $target) => [
                $target->name,
                $target->metric->label(),
                $target->period_start?->format('Y-m-d').' → '.$target->period_end?->format('Y-m-d'),
                $target->departments->isNotEmpty() ? $target->departments->pluck('name')->join(', ') : '—',
                number_format((float) $target->target_value, 2),
                number_format($target->totalAssigned(), 2),
                number_format($target->totalActual(), 2),
                $target->achievementPercentage().'%',
            ])->all(),
            'summary' => [
                'Targets' => $targets->count(),
                'On track (≥80%)' => $targets->filter(fn (Target $t) => $t->achievementPercentage() >= 80)->count(),
                'Avg achievement' => $targets->count() > 0
                    ? round($targets->avg(fn (Target $t) => $t->achievementPercentage()), 1).'%'
                    : '0%',
            ],
            'breakdowns' => [
                [
                    'title' => 'Staff assignments',
                    'columns' => ['Target', 'Staff', 'Assigned', 'Actual', 'Achievement'],
                    'rows' => $assignmentRows->all(),
                ],
            ],
            'charts' => [
                [
                    'title' => 'Achievement by target',
                    'type' => 'bar',
                    'labels' => $targets->pluck('name')->all(),
                    'values' => $targets->map(fn (Target $t) => $t->achievementPercentage())->all(),
                ],
            ],
        ];
    }

    private function staffPerformance(Carbon $from, Carbon $to): array
    {
        $users = User::query()
            ->where('is_active', true)
            ->where('is_super_admin', false)
            ->when(auth()->user()?->organization_id, fn ($q, $orgId) => $q->where('organization_id', $orgId))
            ->with('assignedDepartment')
            ->orderBy('name')
            ->get();

        $quotes = Quote::whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])->get()->groupBy('created_by');
        $invoices = Invoice::whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotIn('status', [InvoiceStatus::Cancelled])
            ->get()
            ->groupBy('created_by');
        $tasks = Task::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->get()->groupBy('assigned_to');
        $appointments = Appointment::whereBetween('starts_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->get()->groupBy('assigned_to');

        $rows = $users->map(function (User $user) use ($quotes, $invoices, $tasks, $appointments) {
            $userQuotes = $quotes->get($user->id, collect());
            $userInvoices = $invoices->get($user->id, collect());
            $userTasks = $tasks->get($user->id, collect());
            $userAppointments = $appointments->get($user->id, collect());

            return [
                $user->name,
                $user->assignedDepartment?->name ?? ($user->department ?: '—'),
                $userQuotes->count(),
                $userQuotes->where('status', QuoteStatus::Accepted)->count(),
                number_format((float) $userInvoices->sum('total_amount'), 2),
                $userTasks->count(),
                $userTasks->where('status', TaskStatus::Completed)->count(),
                $userAppointments->count(),
                $userAppointments->where('status', AppointmentStatus::Completed)->count(),
            ];
        })->filter(fn (array $row) => $row[2] > 0 || $row[4] !== '0.00' || $row[5] > 0 || $row[7] > 0)
            ->values();

        return [
            'title' => 'Staff performance',
            'columns' => ['Staff', 'Department', 'Quotes', 'Accepted quotes', 'Invoiced', 'Tasks', 'Tasks done', 'Appointments', 'Appts done'],
            'rows' => $rows->all(),
            'summary' => [
                'Active contributors' => $rows->count(),
                'Quotes created' => $rows->sum(fn ($row) => (int) $row[2]),
                'Tasks completed' => $rows->sum(fn ($row) => (int) $row[6]),
                'Appointments completed' => $rows->sum(fn ($row) => (int) $row[8]),
            ],
            'breakdowns' => [],
            'charts' => [
                [
                    'title' => 'Invoiced amount by staff',
                    'type' => 'bar',
                    'labels' => $rows->pluck(0)->all(),
                    'values' => $rows->map(fn ($row) => (float) str_replace(',', '', $row[4]))->all(),
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, mixed>  $groups
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function chartFromGroups(Collection $groups): array
    {
        $labels = [];
        $values = [];

        foreach ($groups as $label => $group) {
            $labels[] = (string) $label;
            $values[] = $group->count();
        }

        return compact('labels', 'values');
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function sumByDay(Collection $items, callable $dateKey, callable $amount, Carbon $from, Carbon $to): array
    {
        $buckets = $items->groupBy($dateKey)->map(fn (Collection $group) => round((float) $group->sum($amount), 2));

        return $this->fillDaySeries($buckets, $from, $to);
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function countByDay(Collection $items, callable $dateKey, Carbon $from, Carbon $to): array
    {
        $buckets = $items->groupBy($dateKey)->map(fn (Collection $group) => $group->count());

        return $this->fillDaySeries($buckets, $from, $to);
    }

    /**
     * @param  Collection<string, int|float>  $buckets
     * @return array{labels: array<int, string>, values: array<int, int|float>}
     */
    private function fillDaySeries(Collection $buckets, Carbon $from, Carbon $to): array
    {
        $labels = [];
        $values = [];
        $days = $from->diffInDays($to);
        $groupByWeek = $days > 45;

        if ($groupByWeek) {
            $weekly = [];
            foreach ($buckets as $date => $value) {
                if (! $date) {
                    continue;
                }
                $weekStart = Carbon::parse($date)->startOfWeek()->format('Y-m-d');
                $weekly[$weekStart] = ($weekly[$weekStart] ?? 0) + $value;
            }

            $cursor = $from->copy()->startOfWeek();
            while ($cursor->lte($to)) {
                $key = $cursor->format('Y-m-d');
                $labels[] = $cursor->format('M j');
                $values[] = $weekly[$key] ?? 0;
                $cursor->addWeek();
            }

            return compact('labels', 'values');
        }

        foreach (CarbonPeriod::create($from->toDateString(), $to->toDateString()) as $date) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $values[] = $buckets[$key] ?? 0;
        }

        return compact('labels', 'values');
    }
}
