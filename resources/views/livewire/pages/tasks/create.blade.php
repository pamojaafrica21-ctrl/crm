<?php

use App\Domain\Customers\Models\Customer;
use App\Domain\Organizations\Models\Department;
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
    public array $assignee_ids = [];
    public array $department_ids = [];
    public string $customer_id = '';
    public string $priority = 'medium';
    public string $due_date = '';
    public bool $is_team_task = false;

    public function mount(): void
    {
        $this->authorize('tasks.create');
    }

    public function users()
    {
        $query = User::where('is_active', true)->where('is_super_admin', false)->orderBy('name');

        if (auth()->user()?->organization_id) {
            $query->where('organization_id', auth()->user()->organization_id);
        }

        return $query->get();
    }

    public function departments()
    {
        $query = Department::where('is_active', true)->orderBy('name');

        if (auth()->user()?->organization_id) {
            $query->where('organization_id', auth()->user()->organization_id);
        }

        return $query->get();
    }

    public function customers()
    {
        return Customer::orderBy('last_name')->get();
    }

    public function save(): void
    {
        $this->authorize('tasks.create');
        $this->validate([
            'title' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'assignee_ids' => 'array',
            'assignee_ids.*' => 'integer|exists:users,id',
            'department_ids' => 'array',
            'department_ids.*' => 'integer|exists:departments,id',
        ]);

        $primaryAssignee = $this->assignee_ids[0] ?? null;

        $task = Task::create([
            'property_id' => app(PropertyContext::class)->id(),
            'title' => $this->title,
            'description' => $this->description,
            'assigned_to' => $primaryAssignee,
            'customer_id' => $this->customer_id ?: null,
            'created_by' => auth()->id(),
            'priority' => TaskPriority::from($this->priority),
            'due_date' => $this->due_date ?: null,
            'is_team_task' => $this->is_team_task || count($this->assignee_ids) > 1,
        ]);

        $task->assignees()->sync($this->assignee_ids);
        $task->departments()->sync($this->department_ids);

        $notified = User::whereIn('id', $this->assignee_ids)->get();
        foreach ($notified as $user) {
            CrmNotification::send($user, 'New Task Assigned', "You have been assigned: {$task->title}", route('tasks.show', $task));
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

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Assign to staff</label>
            <div class="flex flex-wrap gap-3 rounded-lg border border-slate-200 p-3">
                @foreach ($this->users() as $u)
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="assignee_ids" value="{{ $u->id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        {{ $u->name }}
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Departments</label>
            <div class="flex flex-wrap gap-3 rounded-lg border border-slate-200 p-3">
                @forelse ($this->departments() as $department)
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="department_ids" value="{{ $department->id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        {{ $department->name }}
                    </label>
                @empty
                    <span class="text-sm text-slate-500">No departments yet.</span>
                @endforelse
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Priority</label>
                <select wire:model="priority" class="w-full rounded-lg border-slate-200">
                    @foreach (['low','medium','high','urgent'] as $p)
                        <option value="{{ $p }}">{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Due Date</label>
                <input type="date" wire:model="due_date" class="w-full rounded-lg border-slate-200">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Customer</label>
            <select wire:model="customer_id" class="w-full rounded-lg border-slate-200">
                <option value="">None</option>
                @foreach ($this->customers() as $c)
                    <option value="{{ $c->id }}">{{ $c->fullName() }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_team_task" class="rounded border-slate-300 text-indigo-600"> Team task</label>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg">Create Task</button>
    </form>
</div>
