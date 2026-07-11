<?php

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

    public function mount(): void
    {
        $this->period_start = now()->startOfMonth()->toDateString();
        $this->period_end = now()->endOfMonth()->toDateString();
        $this->assignments = [['user_id' => '', 'assigned_value' => '']];
    }

    public function users() { return User::where('is_active', true)->orderBy('name')->get(); }

    public function addAssignment(): void { $this->assignments[] = ['user_id' => '', 'assigned_value' => '']; }

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
            if ($a['user_id'] && $a['assigned_value']) {
                TargetAssignment::create([
                    'target_id' => $target->id,
                    'user_id' => $a['user_id'],
                    'assigned_value' => $a['assigned_value'],
                ]);
            }
        }

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
                <div class="flex justify-between mb-2"><span class="text-sm font-medium">Staff Assignments</span><button type="button" wire:click="addAssignment" class="text-xs text-indigo-600">+ Add</button></div>
                @foreach ($assignments as $i => $a)
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <select wire:model="assignments.{{ $i }}.user_id" class="rounded-lg border-slate-200 text-sm">
                            <option value="">Staff member</option>
                            @foreach ($this->users() as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                        </select>
                        <input type="number" wire:model="assignments.{{ $i }}.assigned_value" placeholder="Assigned value" class="rounded-lg border-slate-200 text-sm">
                    </div>
                @endforeach
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg">Create Target</button>
        </form>
    </div>
