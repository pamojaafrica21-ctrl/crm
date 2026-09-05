<?php

use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;
    public bool $editingIsSystem = false;
    public bool $editingIsAdministrator = false;

    public string $name = '';
    public array $permissions = [];

    public function mount(): void
    {
        $this->authorize('roles.view');
    }

    public function with(): array
    {
        $this->ensureTeamContext();

        $orgId = auth()->user()?->organization_id;

        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->where(function ($query) use ($orgId) {
                $query->whereNull('organization_id');

                if ($orgId) {
                    $query->orWhere('organization_id', $orgId);
                }
            })
            ->orderByRaw('organization_id is null desc')
            ->orderBy('name')
            ->get();

        return [
            'roles' => $roles,
        ];
    }

    public function permissionGroups(): array
    {
        $labels = [
            'dashboard' => 'Dashboard',
            'customers' => 'Customers',
            'quotes' => 'Quotes',
            'invoices' => 'Invoices',
            'payments' => 'Payments',
            'tasks' => 'Tasks',
            'appointments' => 'Appointments',
            'targets' => 'Targets',
            'staff' => 'Staff',
            'departments' => 'Departments',
            'roles' => 'Roles',
            'reports' => 'Reports',
            'announcements' => 'Announcements',
            'billing' => 'Billing',
        ];

        $actionLabels = [
            'view' => 'View page',
            'create' => 'Create',
            'update' => 'Update',
            'delete' => 'Delete',
            'convert' => 'Convert',
            'export' => 'Export',
            'manage' => 'Manage subscription',
        ];

        $groups = [];

        foreach (Permission::orderBy('name')->get() as $permission) {
            [$module, $action] = array_pad(explode('.', $permission->name, 2), 2, $permission->name);

            $groups[$module] ??= [
                'label' => $labels[$module] ?? ucfirst($module),
                'permissions' => [],
            ];

            $groups[$module]['permissions'][] = [
                'name' => $permission->name,
                'label' => $actionLabels[$action] ?? ucfirst(str_replace('_', ' ', $action)),
            ];
        }

        return $groups;
    }

    public function openCreate(): void
    {
        $this->authorize('roles.create');
        $this->resetForm();
        $this->showForm = true;
        $this->editingId = null;
        $this->editingIsSystem = false;
        $this->editingIsAdministrator = false;
    }

    public function openEdit(int $roleId): void
    {
        $this->authorize('roles.update');

        $role = $this->findVisibleRole($roleId);

        if ($role->name === 'Administrator') {
            return;
        }

        $this->editingId = $role->id;
        $this->editingIsSystem = is_null($role->organization_id);
        $this->editingIsAdministrator = false;
        $this->name = $role->name;
        $this->permissions = $role->permissions->pluck('name')->all();
        $this->showForm = true;
    }

    public function duplicate(int $roleId): void
    {
        $this->authorize('roles.create');

        $role = $this->findVisibleRole($roleId);

        $this->editingId = null;
        $this->editingIsSystem = false;
        $this->editingIsAdministrator = false;
        $this->name = $role->name.' (Custom)';
        $this->permissions = $role->permissions->pluck('name')->all();
        $this->showForm = true;
        $this->resetValidation();
    }

    public function toggleModule(string $module): void
    {
        if ($this->editingIsAdministrator) {
            return;
        }

        $modulePermissions = collect($this->permissionGroups()[$module]['permissions'] ?? [])
            ->pluck('name')
            ->all();

        $allSelected = empty(array_diff($modulePermissions, $this->permissions));

        if ($allSelected) {
            $this->permissions = array_values(array_diff($this->permissions, $modulePermissions));
        } else {
            $this->permissions = array_values(array_unique([...$this->permissions, ...$modulePermissions]));
        }
    }

    public function save(): void
    {
        $this->ensureTeamContext();
        $orgId = auth()->user()->organization_id;

        if ($this->editingId) {
            $this->authorize('roles.update');
            $role = $this->findVisibleRole($this->editingId);

            if ($role->name === 'Administrator') {
                return;
            }
        } else {
            $this->authorize('roles.create');
            $role = null;
        }

        $nameRules = [
            'required',
            'string',
            'max:100',
            'not_in:Administrator',
        ];

        if (! $role || is_null($role->organization_id) === false) {
            $nameRules[] = Rule::unique('roles', 'name')->where(function ($query) use ($orgId) {
                $query->where(function ($inner) use ($orgId) {
                    $inner->whereNull('organization_id');

                    if ($orgId) {
                        $inner->orWhere('organization_id', $orgId);
                    }
                });
            })->ignore($this->editingId);
        }

        $this->validate([
            'name' => $nameRules,
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($role) {
            // System role names stay fixed; only custom roles can be renamed.
            if (! is_null($role->organization_id)) {
                $role->name = trim($this->name);
                $role->save();
            }

            $role->syncPermissions($this->permissions);
        } else {
            $role = Role::create([
                'name' => trim($this->name),
                'guard_name' => 'web',
                'organization_id' => $orgId,
            ]);
            $role->syncPermissions($this->permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->resetForm();
        $this->showForm = false;
        $this->editingId = null;
        $this->editingIsSystem = false;
        $this->editingIsAdministrator = false;
    }

    public function delete(int $roleId): void
    {
        $this->authorize('roles.delete');
        $this->ensureTeamContext();

        $role = $this->findCustomRole($roleId);

        if ($role->users()->count() > 0) {
            $this->addError('name', 'This role is assigned to staff and cannot be deleted.');

            return;
        }

        $role->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        if ($this->editingId === $roleId) {
            $this->cancel();
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
        $this->editingId = null;
        $this->editingIsSystem = false;
        $this->editingIsAdministrator = false;
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'permissions']);
        $this->resetValidation();
    }

    private function ensureTeamContext(): void
    {
        if (auth()->user()?->organization_id) {
            setPermissionsTeamId(auth()->user()->organization_id);
        }
    }

    private function findVisibleRole(int $roleId): Role
    {
        $orgId = auth()->user()?->organization_id;

        return Role::query()
            ->whereKey($roleId)
            ->where(function ($query) use ($orgId) {
                $query->whereNull('organization_id');

                if ($orgId) {
                    $query->orWhere('organization_id', $orgId);
                }
            })
            ->firstOrFail();
    }

    private function findCustomRole(int $roleId): Role
    {
        return Role::query()
            ->whereKey($roleId)
            ->where('organization_id', auth()->user()?->organization_id)
            ->firstOrFail();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Roles & Permissions</h1>
            <p class="mt-1 text-sm text-slate-500">Create organisation roles and choose which pages and actions each role can use.</p>
        </div>
        @can('roles.create')
            <button type="button" wire:click="openCreate" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shrink-0">
                Add Role
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ $editingId ? 'Edit role permissions' : 'Add role' }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $editingIsSystem
                            ? 'Update the page and action access for this system role.'
                            : 'Set the role name and choose which pages and actions it can use.' }}
                    </p>
                </div>
                <button type="button" wire:click="cancel" class="text-sm text-slate-500 hover:text-slate-800">Cancel</button>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Role name *</label>
                <input type="text"
                       wire:model="name"
                       @disabled($editingIsSystem)
                       class="w-full rounded-lg border-slate-200 max-w-md disabled:bg-slate-50 disabled:text-slate-500">
                @if ($editingIsSystem)
                    <p class="mt-1 text-xs text-slate-500">System role names cannot be changed. Duplicate the role to create a custom named copy.</p>
                @endif
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="flex items-center justify-between gap-4 mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Page & action access</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Choose the defaults for anyone assigned this role.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                    @foreach ($this->permissionGroups() as $module => $group)
                        @php
                            $names = collect($group['permissions'])->pluck('name')->all();
                            $selectedCount = count(array_intersect($names, $permissions));
                            $allSelected = $selectedCount === count($names) && count($names) > 0;
                        @endphp
                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">{{ $group['label'] }}</div>
                                    <div class="text-xs text-slate-500">{{ $selectedCount }}/{{ count($names) }} actions</div>
                                </div>
                                <button type="button" wire:click="toggleModule('{{ $module }}')"
                                        class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                    {{ $allSelected ? 'Revoke all' : 'Grant all' }}
                                </button>
                            </div>
                            <div class="space-y-2">
                                @foreach ($group['permissions'] as $permission)
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox"
                                               wire:model="permissions"
                                               value="{{ $permission['name'] }}"
                                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span>{{ $permission['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                    {{ $editingId ? 'Save permissions' : 'Create role' }}
                </button>
                <button type="button" wire:click="cancel" class="px-4 py-2 border border-slate-200 text-sm font-medium rounded-lg hover:bg-slate-50">
                    Cancel
                </button>
            </div>
        </form>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">Role</th>
                    <th class="text-left px-4 py-3 font-medium">Type</th>
                    <th class="text-left px-4 py-3 font-medium">Permissions</th>
                    <th class="text-left px-4 py-3 font-medium">Staff</th>
                    <th class="text-right px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($roles as $role)
                    @php $isSystem = is_null($role->organization_id); @endphp
                    <tr class="{{ $editingId === $role->id ? 'bg-indigo-50/40' : '' }}">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $role->name }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs {{ $isSystem ? 'bg-slate-100 text-slate-700' : 'bg-indigo-50 text-indigo-700' }}">
                                {{ $isSystem ? 'System' : 'Custom' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $role->name === 'Administrator' ? 'Full access' : $role->permissions->count().' permissions' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $role->users_count }}</td>
                        <td class="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                            @can('roles.create')
                                <button type="button" wire:click="duplicate({{ $role->id }})" class="text-xs font-medium text-slate-600 hover:text-slate-800">
                                    Duplicate
                                </button>
                            @endcan
                            @if ($role->name !== 'Administrator')
                                @can('roles.update')
                                    <button type="button" wire:click="openEdit({{ $role->id }})" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                        Edit permissions
                                    </button>
                                @endcan
                            @endif
                            @if (! $isSystem)
                                @can('roles.delete')
                                    <button type="button" wire:click="delete({{ $role->id }})" wire:confirm="Delete this role?" class="text-xs text-red-600 hover:text-red-700">
                                        Delete
                                    </button>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
