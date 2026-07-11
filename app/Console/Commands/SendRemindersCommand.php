<?php

namespace App\Console\Commands;

use App\Application\Dashboard\KpiService;
use App\Application\Targets\TargetProgressService;
use App\Domain\Appointments\Models\Appointment;
use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Enums\AppointmentStatus;
use App\Domain\Tasks\Models\Task;
use App\Infrastructure\Notifications\CrmNotification;
use Illuminate\Console\Command;

class SendRemindersCommand extends Command
{
    protected $signature = 'crm:send-reminders';

    protected $description = 'Send task and appointment reminders';

    public function handle(KpiService $kpiService, TargetProgressService $targetService): int
    {
        $this->sendTaskReminders();
        $this->sendAppointmentReminders();

        Property::where('is_active', true)->each(function (Property $property) use ($kpiService, $targetService) {
            $kpiService->snapshotDaily($property->id);

            \App\Domain\Targets\Models\Target::withoutGlobalScope('property')
                ->where('property_id', $property->id)
                ->where('period_end', '>=', now())
                ->each(fn ($target) => $targetService->refreshTarget($target));
        });

        return self::SUCCESS;
    }

    private function sendTaskReminders(): void
    {
        Task::withoutGlobalScope('property')
            ->whereDate('due_date', today())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with('assignee')
            ->each(function (Task $task) {
                if ($task->assignee) {
                    CrmNotification::send(
                        $task->assignee,
                        'Task Due Today',
                        "Task \"{$task->title}\" is due today.",
                        route('tasks.index')
                    );
                }
            });
    }

    private function sendAppointmentReminders(): void
    {
        Appointment::withoutGlobalScope('property')
            ->whereBetween('starts_at', [now(), now()->addDay()])
            ->whereIn('status', [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed])
            ->whereNull('reminder_sent_at')
            ->with('assignee')
            ->each(function (Appointment $appointment) {
                if ($appointment->assignee) {
                    CrmNotification::send(
                        $appointment->assignee,
                        'Upcoming Appointment',
                        "Appointment \"{$appointment->title}\" starts at {$appointment->starts_at->format('M j, g:i A')}.",
                        route('appointments.index')
                    );
                }
                $appointment->update(['reminder_sent_at' => now()]);
            });
    }
}
