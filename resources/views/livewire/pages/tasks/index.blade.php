<?php

use App\Domain\Tasks\Models\Task;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function with(): array
    {
        return [
            'tasks' => Task::with(['assignee', 'customer'])
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate(15),
        ];
    }

    public function updateStatus(int $taskId, string $status): void
    {
        $this->authorize('tasks.update');
        $task = Task::findOrFail($taskId);
        $task->update([
            'status' => $status,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);
    }
}; ?>

<div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">Tasks</h1>
            @can('tasks.create')
                <a href="{{ route('tasks.create') }}" wire:navigate class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">New Task</a>
            @endcan
        </div>
        <div class="flex gap-2">
            <button wire:click="$set('statusFilter', '')" class="px-3 py-1 text-xs rounded-full {{ !$statusFilter ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100' }}">All</button>
            @foreach (['pending', 'in_progress', 'completed'] as $s)
                <button wire:click="$set('statusFilter', '{{ $s }}')" class="px-3 py-1 text-xs rounded-full {{ $statusFilter === $s ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100' }} capitalize">{{ str_replace('_', ' ', $s) }}</button>
            @endforeach
        </div>
        <div class="space-y-3">
            @forelse ($tasks as $task)
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 flex items-center justify-between">
                    <div>
                        <a href="{{ route('tasks.show', $task) }}" wire:navigate class="font-medium text-slate-900 hover:text-indigo-600">{{ $task->title }}</a>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ $task->assignee?->name ?? 'Unassigned' }}
                            @if ($task->due_date) · Due {{ $task->due_date->format('M j') }} @endif
                            · <span class="capitalize">{{ $task->priority->label() }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-slate-100 rounded-full text-xs">{{ $task->status->label() }}</span>
                        @can('tasks.update')
                            @if ($task->status->value !== 'completed')
                                <button wire:click="updateStatus({{ $task->id }}, 'completed')" class="text-xs text-green-600 hover:text-green-800">Complete</button>
                            @endif
                        @endcan
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-slate-500">No tasks found.</div>
            @endforelse
        </div>
        {{ $tasks->links() }}
    </div>
