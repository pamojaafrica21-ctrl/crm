<?php

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Appointments\Models\AppointmentType;
use App\Domain\Customers\Models\Customer;
use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Shared\Enums\AppointmentStatus;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component
{
    public string $title = '';
    public string $appointment_type_id = '';
    public string $customer_id = '';
    public string $assigned_to = '';
    public string $starts_at = '';
    public string $ends_at = '';
    public string $notes = '';
    public string $location = '';

    public function types() { return AppointmentType::where('is_active', true)->get(); }
    public function customers() { return Customer::orderBy('last_name')->get(); }
    public function users() { return User::where('is_active', true)->orderBy('name')->get(); }

    public function save(): void
    {
        $this->authorize('appointments.create');
        $this->validate([
            'title' => 'required|string|max:255',
            'appointment_type_id' => 'required|exists:appointment_types,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        Appointment::create([
            'property_id' => app(PropertyContext::class)->id(),
            'appointment_type_id' => $this->appointment_type_id,
            'customer_id' => $this->customer_id ?: null,
            'assigned_to' => $this->assigned_to ?: auth()->id(),
            'created_by' => auth()->id(),
            'title' => $this->title,
            'notes' => $this->notes,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'location' => $this->location,
            'status' => AppointmentStatus::Scheduled,
        ]);

        $this->redirect(route('appointments.index'), navigate: true);
    }
}; ?>

<div class="max-w-2xl space-y-6">
        <h1 class="text-2xl font-bold text-slate-900">Schedule Appointment</h1>
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Title *</label>
                <input type="text" wire:model="title" class="w-full rounded-lg border-slate-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Type *</label>
                <select wire:model="appointment_type_id" class="w-full rounded-lg border-slate-200">
                    <option value="">Select type...</option>
                    @foreach ($this->types() as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Start *</label>
                    <input type="datetime-local" wire:model="starts_at" class="w-full rounded-lg border-slate-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">End *</label>
                    <input type="datetime-local" wire:model="ends_at" class="w-full rounded-lg border-slate-200">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Customer</label>
                    <select wire:model="customer_id" class="w-full rounded-lg border-slate-200">
                        <option value="">None</option>
                        @foreach ($this->customers() as $c)<option value="{{ $c->id }}">{{ $c->fullName() }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Assigned To</label>
                    <select wire:model="assigned_to" class="w-full rounded-lg border-slate-200">
                        <option value="">Me</option>
                        @foreach ($this->users() as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <input type="text" wire:model="location" placeholder="Location" class="w-full rounded-lg border-slate-200 text-sm">
            <textarea wire:model="notes" rows="2" placeholder="Notes..." class="w-full rounded-lg border-slate-200 text-sm"></textarea>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg">Schedule</button>
        </form>
    </div>
