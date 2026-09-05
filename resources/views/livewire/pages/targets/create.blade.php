<?php

use App\Domain\Organizations\Models\Department;
use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Shared\Enums\TargetMetric;
use App\Domain\Shared\Enums\TargetPeriod;
use App\Domain\Targets\Models\Target;
use App\Domain\Targets\Models\TargetAssignment;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $metric = 'revenue';
    public string $period = 'monthly';
    public string $period_start = '';
    public string $period_end = '';
    public string $target_value = '';
    public string $description = '';
    public array $assignments = [];
    public array $department_ids = [];

    public function mount(): void
    {
        $this->authorize('targets.create');
        $this->period_start = now()->startOfMonth()->toDateString();
        $this->period_end = now()->endOfMonth()->toDateString();
        $this->assignments = [['user_id' => '', 'assigned_value' => '']];
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

    public function addAssignment(): void
    {
        $this->assignments[] = ['user_id' => '', 'assigned_value' => ''];
    }

    public function removeAssignment(int $index): void
    {
        unset($this->assignments[$index]);
        $this->assignments = array_values($this->assignments);

        if ($this->assignments === []) {
            $this->assignments = [['user_id' => '', 'assigned_value' => '']];
        }
    }

    public function save(): void
    {
        $this->authorize('targets.create');
        $this->validate([
            'name' => 'required|string|max:255',
            'metric' => 'required|string',
            'period' => 'required|string',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'target_value' => 'required|numeric|min:0',
            'department_ids' => 'array',
            'department_ids.*' => 'integer|exists:departments,id',
        ]);

        $target = Target::create([
            'property_id' => app(PropertyContext::class)->id(),
            'created_by' => auth()->id(),
            'name' => $this->name,
            'metric' => TargetMetric::from($this->metric),
            'period' => TargetPeriod::from($this->period),
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'target_value' => $this->target_value,
            'description' => $this->description,
            'currency' => app(PropertyContext::class)->property()?->currency,
        ]);

        foreach ($this->assignments as $a) {
            if ($a['user_id'] && $a['assigned_value'] !== '') {
                TargetAssignment::create([
                    'target_id' => $target->id,
                    'user_id' => $a['user_id'],
                    'assigned_value' => $a['assigned_value'],
                ]);
            }
        }

        $target->departments()->sync($this->department_ids);

        $this->redirect(route('targets.index'), navigate: true);
    }
}; ?>

<div class="max-w-2xl space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">New Target</h1>
    <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
        <input type="text" wire:model="name" placeholder="Target name *" class="w-full rounded-lg border-slate-200">
        <div class="grid grid-cols-2 gap-4">
            <select wire:model="metric" class="rounded-lg border-slate-200">
                @foreach (['revenue','bookings','conversion','appointments','custom'] as $m)
                    <option value="{{ $m }}">{{ ucfirst($m) }}</option>
                @endforeach
            </select>
            <select wire:model="period" class="rounded-lg border-slate-200">
                @foreach (['monthly','quarterly','annual'] as $p)
                    <option value="{{ $p }}">{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-3 gap-4">
            <input type="date" wire:model="period_start" class="rounded-lg border-slate-200">
            <input type="date" wire:model="period_end" class="rounded-lg border-slate-200">
            <input type="number" wire:model="target_value" step="0.01" placeholder="Target value *" class="rounded-lg border-slate-200">
        </div>

        <div>
            <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-slate-700">Staff Assignments</span>
                <button type="button" wire:click="addAssignment" class="text-xs text-indigo-600">+ Add</button>
            </div>
            @foreach ($assignments as $i => $a)
                <div class="grid grid-cols-[1fr_1fr_auto] gap-2 mb-2">
                    <select wire:model="assignments.{{ $i }}.user_id" class="rounded-lg border-slate-200 text-sm">
                        <option value="">Staff member</option>
                        @foreach ($this->users() as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" wire:model="assignments.{{ $i }}.assigned_value" placeholder="Assigned value" class="rounded-lg border-slate-200 text-sm">
                    <button type="button" wire:click="removeAssignment({{ $i }})" class="text-xs text-slate-400 hover:text-slate-700 px-2">Remove</button>
                </div>
            @endforeach
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
                    <span class="text-sm text-slate-500">No departments yet. Create them under Departments.</span>
                @endforelse
            </div>
        </div>

        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg">Create Target</button>
    </form>
</div>
