<?php

use App\Domain\Organizations\Models\Department;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public ?int $editingId = null;
    public ?int $viewingId = null;

    public string $name = '';
    public string $description = '';
    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('departments.view');
    }

    public function with(): array
    {
        $orgId = auth()->user()?->organization_id;

        $query = Department::query()
            ->withCount('users')
            ->orderBy('name');

        if ($orgId) {
            $query->where('organization_id', $orgId);
        }

        $viewingDepartment = null;
        $departmentMembers = collect();

        if ($this->viewingId) {
            $viewingDepartment = $this->findDepartment($this->viewingId);
            $membersQuery = User::query()
                ->with('roles')
                ->where('department_id', $viewingDepartment->id)
                ->where('is_super_admin', false)
                ->orderBy('name');

            if ($orgId) {
                $membersQuery->where('organization_id', $orgId);
            }

            $departmentMembers = $membersQuery->get();
        }

        return [
            'departments' => $query->paginate(15),
            'viewingDepartment' => $viewingDepartment,
            'departmentMembers' => $departmentMembers,
        ];
    }

    public function openCreate(): void
    {
        $this->authorize('departments.create');
        $this->resetForm();
        $this->showForm = true;
        $this->editingId = null;
        $this->viewingId = null;
    }

    public function openView(int $departmentId): void
    {
        $this->authorize('departments.view');
        $this->findDepartment($departmentId);
        $this->viewingId = $departmentId;
        $this->showForm = false;
        $this->editingId = null;
    }

    public function openEdit(int $departmentId): void
    {
        $this->authorize('departments.update');

        $department = $this->findDepartment($departmentId);

        $this->editingId = $department->id;
        $this->name = $department->name;
        $this->description = $department->description ?? '';
        $this->is_active = $department->is_active;
        $this->showForm = true;
        $this->viewingId = null;
    }

    public function save(): void
    {
        $orgId = auth()->user()->organization_id;

        if ($this->editingId) {
            $this->authorize('departments.update');
            $department = $this->findDepartment($this->editingId);
        } else {
            $this->authorize('departments.create');
            $department = null;
        }

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('departments', 'name')
                    ->where(fn ($query) => $query->where('organization_id', $orgId))
                    ->ignore($this->editingId),
            ],
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $payload = [
            'name' => trim($this->name),
            'description' => $this->description ?: null,
            'is_active' => $this->is_active,
            'organization_id' => $orgId,
        ];

        if ($department) {
            $department->update($payload);
            $department->users()->update(['department' => $department->name]);
        } else {
            Department::create($payload);
        }

        $this->resetForm();
        $this->showForm = false;
        $this->editingId = null;
    }

    public function toggleActive(int $departmentId): void
    {
        $this->authorize('departments.update');

        $department = $this->findDepartment($departmentId);
        $department->update(['is_active' => ! $department->is_active]);
    }

    public function delete(int $departmentId): void
    {
        $this->authorize('departments.delete');

        $department = $this->findDepartment($departmentId);
        $department->users()->update([
            'department_id' => null,
            'department' => null,
        ]);
        $department->delete();

        if ($this->editingId === $departmentId || $this->viewingId === $departmentId) {
            $this->cancel();
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
        $this->editingId = null;
        $this->viewingId = null;
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'description']);
        $this->is_active = true;
        $this->resetValidation();
    }

    private function findDepartment(int $departmentId): Department
    {
        $query = Department::query()->whereKey($departmentId);

        if (auth()->user()?->organization_id) {
            $query->where('organization_id', auth()->user()->organization_id);
        }

        return $query->firstOrFail();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Departments</h1>
            <p class="mt-1 text-sm text-slate-500">Register departments for your organisation and assign staff to them.</p>
        </div>
        @can('departments.create')
            <button type="button" wire:click="openCreate" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shrink-0">
                Add Department
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ $editingId ? 'Edit department' : 'Add department' }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Departments help organise staff within your company.
                    </p>
                </div>
                <button type="button" wire:click="cancel" class="text-sm text-slate-500 hover:text-slate-800">Cancel</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
                    <input type="text" wire:model="name" class="w-full rounded-lg border-slate-200">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <input type="text" wire:model="description" class="w-full rounded-lg border-slate-200">
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                Active
            </label>

            <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                    {{ $editingId ? 'Save changes' : 'Create department' }}
                </button>
                <button type="button" wire:click="cancel" class="px-4 py-2 border border-slate-200 text-sm font-medium rounded-lg hover:bg-slate-50">
                    Cancel
                </button>
            </div>
        </form>
    @endif

    @if ($viewingDepartment)
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $viewingDepartment->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $viewingDepartment->description ?: 'Staff assigned to this department.' }}
                    </p>
                </div>
                <button type="button" wire:click="cancel" class="text-sm text-slate-500 hover:text-slate-800">Close</button>
            </div>

            <div class="overflow-x-auto rounded-lg border border-slate-100">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium">Name</th>
                            <th class="text-left px-4 py-3 font-medium">Email</th>
                            <th class="text-left px-4 py-3 font-medium">Role</th>
                            <th class="text-left px-4 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($departmentMembers as $member)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $member->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $member->email }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-700">
                                        {{ $member->roles->first()?->name ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $member->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $member->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">No staff in this department yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">Name</th>
                    <th class="text-left px-4 py-3 font-medium">Description</th>
                    <th class="text-left px-4 py-3 font-medium">Staff</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                    <th class="text-right px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($departments as $department)
                    <tr class="{{ $viewingId === $department->id || $editingId === $department->id ? 'bg-indigo-50/40' : '' }}">
                        <td class="px-4 py-3 font-medium text-slate-900">
                            <button type="button" wire:click="openView({{ $department->id }})" class="hover:text-indigo-700">
                                {{ $department->name }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $department->description ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <button type="button" wire:click="openView({{ $department->id }})" class="text-slate-600 hover:text-indigo-700">
                                {{ $department->users_count }}
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $department->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                            <button type="button" wire:click="openView({{ $department->id }})" class="text-xs font-medium text-slate-600 hover:text-slate-800">
                                View staff
                            </button>
                            @can('departments.update')
                                <button type="button" wire:click="openEdit({{ $department->id }})" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                    Edit
                                </button>
                                <button type="button" wire:click="toggleActive({{ $department->id }})" class="text-xs text-slate-500 hover:text-slate-800">
                                    {{ $department->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            @endcan
                            @can('departments.delete')
                                <button type="button" wire:click="delete({{ $department->id }})" wire:confirm="Delete this department? Staff will be unassigned from it." class="text-xs text-red-600 hover:text-red-700">
                                    Delete
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">No departments yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $departments->links() }}</div>
    </div>
</div>
