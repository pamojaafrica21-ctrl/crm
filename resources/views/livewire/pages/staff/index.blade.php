<?php

use App\Domain\Properties\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new class extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $department = '';
    public string $role = '';
    public array $property_ids = [];

    public function with(): array
    {
        return ['staff' => User::with('roles', 'properties')->orderBy('name')->paginate(15)];
    }

    public function properties() { return Property::where('is_active', true)->get(); }
    public function roles() { return Role::orderBy('name')->get(); }

    public function save(): void
    {
        $this->authorize('staff.create');
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make('password'),
            'phone' => $this->phone,
            'department' => $this->department,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $user->assignRole($this->role);
        if ($this->property_ids) {
            $user->properties()->sync($this->property_ids);
        }

        $this->reset(['name', 'email', 'phone', 'department', 'role', 'property_ids', 'showForm']);
    }

    public function toggleActive(int $userId): void
    {
        $this->authorize('staff.update');
        $user = User::findOrFail($userId);
        $user->update(['is_active' => ! $user->is_active]);
    }
}; ?>

<div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">Staff Management</h1>
            @can('staff.create')
                <button wire:click="$toggle('showForm')" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg">Add Staff</button>
            @endcan
        </div>

        @if ($showForm)
            <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" wire:model="name" placeholder="Full name *" class="rounded-lg border-slate-200">
                    <input type="email" wire:model="email" placeholder="Email *" class="rounded-lg border-slate-200">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" wire:model="phone" placeholder="Phone" class="rounded-lg border-slate-200">
                    <input type="text" wire:model="department" placeholder="Department" class="rounded-lg border-slate-200">
                </div>
                <select wire:model="role" class="w-full rounded-lg border-slate-200">
                    <option value="">Select role *</option>
                    @foreach ($this->roles() as $r)<option value="{{ $r->name }}">{{ $r->name }}</option>@endforeach
                </select>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->properties() as $p)
                        <label class="flex items-center gap-1 text-sm">
                            <input type="checkbox" wire:model="property_ids" value="{{ $p->id }}"> {{ $p->name }}
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-slate-500">Default password: password</p>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Create Staff</button>
            </form>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50"><tr>
                    <th class="text-left px-4 py-3">Name</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Role</th>
                    <th class="text-left px-4 py-3">Department</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-right px-4 py-3">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($staff as $member)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $member->name }}</td>
                            <td class="px-4 py-3">{{ $member->email }}</td>
                            <td class="px-4 py-3">{{ $member->roles->first()?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $member->department ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $member->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $member->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @can('staff.update')
                                    <button wire:click="toggleActive({{ $member->id }})" class="text-xs text-indigo-600">Toggle</button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $staff->links() }}</div>
        </div>
    </div>
