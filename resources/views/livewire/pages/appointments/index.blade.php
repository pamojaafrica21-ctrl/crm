<?php

use App\Domain\Appointments\Models\Appointment;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public function with(): array
    {
        return [
            'appointments' => Appointment::with(['type', 'customer', 'assignee', 'departments'])
                ->orderBy('starts_at')
                ->paginate(15),
        ];
    }
}; ?>

<div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">Appointments</h1>
            @can('appointments.create')
                <a href="{{ route('appointments.create') }}" wire:navigate class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Schedule</a>
            @endcan
        </div>
        <div class="space-y-3">
            @forelse ($appointments as $appt)
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-1 h-12 rounded-full" style="background: {{ $appt->type->color }}"></div>
                        <div>
                            <div class="font-medium text-slate-900">{{ $appt->title }}</div>
                            <div class="text-xs text-slate-500">{{ $appt->type->name }} · {{ $appt->starts_at->format('M j, g:i A') }}</div>
                            @if ($appt->customer)<div class="text-xs text-slate-400">{{ $appt->customer->fullName() }}</div>@endif
                            @if ($appt->departments->isNotEmpty())
                                <div class="text-xs text-slate-400">Depts: {{ $appt->departments->pluck('name')->join(', ') }}</div>
                            @endif
                        </div>
                    </div>
                    <span class="px-2 py-0.5 bg-slate-100 rounded-full text-xs">{{ $appt->status->label() }}</span>
                </div>
            @empty
                <div class="text-center py-8 text-slate-500">No appointments scheduled.</div>
            @endforelse
        </div>
        {{ $appointments->links() }}
    </div>
