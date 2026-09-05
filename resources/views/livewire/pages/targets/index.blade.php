<?php

use App\Application\Targets\TargetProgressService;
use App\Domain\Targets\Models\Target;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public function with(): array
    {
        return ['targets' => Target::with(['assignments.user', 'departments', 'creator'])->latest()->paginate(15)];
    }

    public function refreshProgress(int $targetId, TargetProgressService $service): void
    {
        $target = Target::findOrFail($targetId);
        $service->refreshTarget($target);
    }
}; ?>

<div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">Targets</h1>
            @can('targets.create')
                <a href="{{ route('targets.create') }}" wire:navigate class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">New Target</a>
            @endcan
        </div>
        <div class="space-y-4">
            @forelse ($targets as $target)
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ $target->name }}</h3>
                            <p class="text-xs text-slate-500">{{ $target->metric->label() }} · {{ $target->period->label() }} · {{ $target->period_start->format('M j') }} – {{ $target->period_end->format('M j, Y') }}</p>
                            @if ($target->departments->isNotEmpty())
                                <p class="text-xs text-slate-500 mt-1">Depts: {{ $target->departments->pluck('name')->join(', ') }}</p>
                            @endif
                        </div>
                        <button wire:click="refreshProgress({{ $target->id }})" class="text-xs text-indigo-600">Refresh</button>
                    </div>
                    <div class="mb-2">
                        <div class="flex justify-between text-sm mb-1">
                            <span>Overall Progress</span>
                            <span class="font-medium">{{ $target->achievementPercentage() }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $target->achievementPercentage() }}%"></div>
                        </div>
                    </div>
                    @foreach ($target->assignments as $assignment)
                        <div class="flex justify-between text-xs text-slate-600 py-1">
                            <span>{{ $assignment->user->name }}</span>
                            <span>{{ number_format($assignment->actual_value, 0) }} / {{ number_format($assignment->assigned_value, 0) }} ({{ $assignment->achievementPercentage() }}%)</span>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="text-center py-8 text-slate-500">No targets set.</div>
            @endforelse
        </div>
        {{ $targets->links() }}
    </div>
