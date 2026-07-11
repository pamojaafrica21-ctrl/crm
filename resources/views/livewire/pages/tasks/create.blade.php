<?php

use App\Domain\Customers\Models\Customer;
use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Shared\Enums\TaskPriority;
use App\Domain\Tasks\Models\Task;
use App\Infrastructure\Notifications\CrmNotification;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component
{
    public string $title = '';
    public string $description = '';
    public string $assigned_to = '';
    public string $customer_id = '';
    public string $priority = 'medium';
    public string $due_date = '';
    public bool $is_team_task = false;

    public function users() { return User::where('is_active', true)->orderBy('name')->get(); }
    public function customers() { return Customer::orderBy('last_name')->get(); }

    public function save(): void
    {
        $this->authorize('tasks.create');
        $this->validate([
            'title' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
        ]);

        $task = Task::create([
            'property_id' => app(PropertyContext::class)->id(),
            'title' => $this->title,
            'description' => $this->description,
            'assigned_to' => $this->assigned_to ?: null,
            'customer_id' => $this->customer_id ?: null,
            'created_by' => auth()->id(),
            'priority' => TaskPriority::from($this->priority),
            'due_date' => $this->due_date ?: null,
            'is_team_task' => $this->is_team_task,
        ]);

        if ($task->assignee) {
            CrmNotification::send($task->assignee, 'New Task Assigned', "You have been assigned: {$task->title}", route('tasks.show', $task));
        }

        $this->redirect(route('tasks.show', $task), navigate: true);
    }
}; ?>

<div class="max-w-2xl space-y-6">
        <h1 class="text-2xl font-bold text-slate-900">New Task</h1>
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Title *</label>
                <input type="text" wire:model="title" class="w-full rounded-lg border-slate-200">
            </div>
            <textarea wire:model="description" rows="3" placeholder="Description..." class="w-full rounded-lg border-slate-200 text-sm"></textarea>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Assign To</label>
                    <select wire:model="assigned_to" class="w-full rounded-lg border-slate-200">
                        <option value="">Unassigned</option>
                        @foreach ($this->users() as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Priority</label>
                    <select wire:model="priority" class="w-full rounded-lg border-slate-200">
                        @foreach (['low','medium','high','urgent'] as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Due Date</label>
                    <input type="date" wire:model="due_date" class="w-full rounded-lg border-slate-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Customer</label>
                    <select wire:model="customer_id" class="w-full rounded-lg border-slate-200">
                        <option value="">None</option>
                        @foreach ($this->customers() as $c)<option value="{{ $c->id }}">{{ $c->fullName() }}</option>@endforeach
                    </select>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_team_task"> Team task</label>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg">Create Task</button>
        </form>
    </div>
