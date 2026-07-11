<?php

namespace App\Application\Targets;

use App\Domain\Sales\Models\Invoice;
use App\Domain\Shared\Enums\TargetMetric;
use App\Domain\Targets\Models\Target;
use App\Domain\Targets\Models\TargetAssignment;
use Carbon\Carbon;

class TargetProgressService
{
    public function refreshTarget(Target $target): void
    {
        foreach ($target->assignments as $assignment) {
            $actual = $this->calculateActual($target, $assignment->user_id);
            $assignment->update(['actual_value' => $actual]);
        }
    }

    private function calculateActual(Target $target, int $userId): float
    {
        $from = Carbon::parse($target->period_start)->startOfDay();
        $to = Carbon::parse($target->period_end)->endOfDay();

        return match ($target->metric) {
            TargetMetric::Revenue => (float) Invoice::withoutGlobalScope('property')
                ->where('property_id', $target->property_id)
                ->where('created_by', $userId)
                ->whereBetween('issue_date', [$from, $to])
                ->sum('total_amount'),
            TargetMetric::Bookings => (float) \App\Domain\Customers\Models\Reservation::withoutGlobalScope('property')
                ->where('property_id', $target->property_id)
                ->whereBetween('check_in', [$from, $to])
                ->count(),
            TargetMetric::Appointments => (float) \App\Domain\Appointments\Models\Appointment::withoutGlobalScope('property')
                ->where('property_id', $target->property_id)
                ->where('assigned_to', $userId)
                ->whereBetween('starts_at', [$from, $to])
                ->count(),
            default => (float) $target->assignments()->where('user_id', $userId)->value('actual_value') ?? 0,
        };
    }
}
