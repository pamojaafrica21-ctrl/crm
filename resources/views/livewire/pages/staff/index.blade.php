<?php

use App\Domain\Organizations\Models\Department;
use App\Domain\Properties\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new class extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public bool $showAccess = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public mixed $department_id = null;
    public string $role = '';
    public array $property_ids = [];
    public array $permissions = [];

    public function mount(): void
    {
        $this->authorize('staff.view');
    }

    public function with(): array
    {
        $query = User::with(['roles.permissions', 'properties', 'permissions', 'assignedDepartment'])
            ->where('is_super_admin', false)
            ->orderBy('name');

        if (auth()->user()?->organization_id) {
            $query->where('organization_id', auth()->user()->organization_id);
        }

        return [
            'staff' => $query->paginate(15),
        ];
    }

    public function properties()
    {
        $query = Property::where('is_active', true)->orderBy('name');

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

    public function roles()
    {
        $orgId = auth()->user()?->organization_id;

        return Role::query()
            ->where(function ($query) use ($orgId) {
                $query->whereNull('organization_id');

                if ($orgId) {
                    $query->orWhere('organization_id', $orgId);
                }
            })
            ->orderBy('name')
            ->get();
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
        $this->authorize('staff.create');
        $this->resetForm();
        $this->property_ids = $this->defaultPropertyIds();
        $this->showForm = true;
        $this->showAccess = false;
        $this->editingId = null;
    }

    public function editAccess(int $userId): void
    {
        $this->authorize('staff.update');

        $user = User::with(['roles', 'properties', 'permissions', 'assignedDepartment'])->findOrFail($userId);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->department_id = $user->department_id;
        $this->role = $user->roles->first()?->name ?? '';
        $this->property_ids = $user->properties->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->permissions = $user->getDirectPermissions()->isNotEmpty()
            ? $user->getDirectPermissions()->pluck('name')->all()
            : $user->getPermissionsViaRoles()->pluck('name')->all();

        $this->showAccess = true;
        $this->showForm = false;
    }

    public function updatedRole(string $value): void
    {
        if ($value === '' || $value === 'Administrator') {
            $this->permissions = $value === 'Administrator'
                ? Permission::orderBy('name')->pluck('name')->all()
                : [];

            return;
        }

        if (auth()->user()?->organization_id) {
            setPermissionsTeamId(auth()->user()->organization_id);
        }

        $role = Role::findByName($value);
        $this->permissions = $role->permissions->pluck('name')->all();
    }

    public function toggleModule(string $module): void
    {
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
        $this->authorize('staff.create');

        $orgId = auth()->user()->organization_id;

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string',
            'phone' => 'nullable|string|max:50',
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('organization_id', $orgId)->where('is_active', true)),
            ],
            'property_ids' => 'array',
            'permissions' => 'array',
        ]);

        $department = $this->resolveDepartment();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make('password'),
            'phone' => $this->phone ?: null,
            'department_id' => $department?->id,
            'department' => $department?->name,
            'email_verified_at' => now(),
            'is_active' => true,
            'organization_id' => $orgId,
        ]);

        if ($orgId) {
            setPermissionsTeamId($orgId);
        }
        $user->syncRoles([$this->role]);
        $user->syncPermissions($this->role === 'Administrator' ? [] : $this->permissions);
        $user->properties()->sync($this->resolvedPropertyIds());

        $this->resetForm();
        $this->showForm = false;
    }

    public function updateAccess(): void
    {
        $this->authorize('staff.update');

        $user = User::findOrFail($this->editingId);
        $orgId = auth()->user()->organization_id;

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => 'required|string',
            'phone' => 'nullable|string|max:50',
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('organization_id', $orgId)->where('is_active', true)),
            ],
            'property_ids' => 'array',
            'permissions' => 'array',
        ]);

        if ($user->id === auth()->id() && $this->role !== 'Administrator' && ! in_array('staff.update', $this->permissions, true)) {
            $this->addError('permissions', 'You cannot remove your own ability to manage staff.');

            return;
        }

        $department = $this->resolveDepartment();

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'department_id' => $department?->id,
            'department' => $department?->name,
        ]);

        if ($orgId) {
            setPermissionsTeamId($orgId);
        }
        $user->syncRoles([$this->role]);
        $user->syncPermissions($this->role === 'Administrator' ? [] : $this->permissions);
        $user->properties()->sync($this->resolvedPropertyIds());

        $this->resetForm();
        $this->showAccess = false;
        $this->editingId = null;
    }

    public function toggleActive(int $userId): void
    {
        $this->authorize('staff.update');

        if ($userId === auth()->id()) {
            return;
        }

        $user = User::findOrFail($userId);
        $user->update(['is_active' => ! $user->is_active]);
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
        $this->showAccess = false;
        $this->editingId = null;
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'email', 'phone', 'department_id', 'role', 'property_ids', 'permissions']);
        $this->resetValidation();
    }

    private function resolveDepartment(): ?Department
    {
        if ($this->department_id === null || $this->department_id === '') {
            return null;
        }

        return $this->departments()->firstWhere('id', (int) $this->department_id);
    }

    private function defaultPropertyIds(): array
    {
        $properties = $this->properties();

        if ($properties->count() === 1) {
            return [(string) $properties->first()->id];
        }

        return [];
    }

    private function resolvedPropertyIds(): array
    {
        $properties = $this->properties();

        if ($properties->count() === 1) {
            return [$properties->first()->id];
        }

        return $this->property_ids;
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Staff Management</h1>
            <p class="mt-1 text-sm text-slate-500">Create staff accounts and control which pages and actions each person can use.</p>
        </div>
        @can('staff.create')
            <button type="button" wire:click="openCreate" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shrink-0">
                Add Staff
            </button>
        @endcan
    </div>

    @if ($showForm || $showAccess)
        <form wire:submit="{{ $showAccess ? 'updateAccess' : 'save' }}" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ $showAccess ? 'Manage access' : 'Add staff member' }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $showAccess
                            ? 'Update their profile, role, properties, and page/action permissions.'
                            : 'Choose a role to start from, then adjust page and action access as needed.' }}
                    </p>
                </div>
                <button type="button" wire:click="cancel" class="text-sm text-slate-500 hover:text-slate-800">Cancel</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Full name *</label>
                    <input type="text" wire:model="name" class="w-full rounded-lg border-slate-200">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
                    <input type="email" wire:model="email" class="w-full rounded-lg border-slate-200">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                    <input type="text" wire:model="phone" class="w-full rounded-lg border-slate-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Department</label>
                    <select wire:model="department_id" class="w-full rounded-lg border-slate-200">
                        <option value="">No department</option>
                        @foreach ($this->departments() as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @can('departments.create')
                        <p class="mt-1 text-xs text-slate-500">
                            Manage departments on the
                            <a href="{{ route('departments.index') }}" wire:navigate class="text-indigo-600 hover:text-indigo-700">Departments</a>
                            page.
                        </p>
                    @endcan
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Role *</label>
                    <select wire:model.live="role" class="w-full rounded-lg border-slate-200">
                        <option value="">Select role</option>
                        @foreach ($this->roles() as $r)
                            <option value="{{ $r->name }}">{{ $r->name }}{{ is_null($r->organization_id) ? '' : ' (Custom)' }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Changing the role resets the access checklist to that role’s defaults.</p>
                    @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    @if ($this->properties()->count() > 1)
                        <label class="block text-sm font-medium text-slate-700 mb-1">Properties</label>
                        <div class="flex flex-wrap gap-3 rounded-lg border border-slate-200 p-3">
                            @foreach ($this->properties() as $p)
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" wire:model="property_ids" value="{{ $p->id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    {{ $p->name }}
                                </label>
                            @endforeach
                        </div>
                    @else
                        <label class="block text-sm font-medium text-slate-700 mb-1">Company</label>
                        <p class="text-sm text-slate-700 rounded-lg border border-slate-200 px-3 py-2 bg-slate-50">
                            {{ $this->properties()->first()?->name ?? 'Main Property' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Staff are scoped to your organisation’s properties.</p>
                    @endif
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between gap-4 mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Page & action access</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Grant or revoke access to CRM pages and the actions on them.</p>
                    </div>
                </div>

                @if ($role === 'Administrator')
                    <div class="rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                        Administrators have full access to every page and action. Individual permissions are not required.
                    </div>
                @else
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
                                            @if (str_ends_with($permission['name'], '.view'))
                                                <span class="text-[11px] uppercase tracking-wide text-slate-400">page</span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('permissions') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                @endif
            </div>

            @if ($showForm)
                <p class="text-xs text-slate-500">Default password: <span class="font-medium text-slate-700">password</span></p>
            @endif

            <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                    {{ $showAccess ? 'Save access' : 'Create staff' }}
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
                    <th class="text-left px-4 py-3 font-medium">Name</th>
                    <th class="text-left px-4 py-3 font-medium">Email</th>
                    <th class="text-left px-4 py-3 font-medium">Department</th>
                    <th class="text-left px-4 py-3 font-medium">Role</th>
                    <th class="text-left px-4 py-3 font-medium">Access</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                    <th class="text-right px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($staff as $member)
                    @php
                        $accessCount = $member->hasRole('Administrator')
                            ? 'Full'
                            : ($member->getDirectPermissions()->isNotEmpty()
                                ? $member->getDirectPermissions()->count()
                                : $member->getPermissionsViaRoles()->count());
                    @endphp
                    <tr class="{{ $editingId === $member->id ? 'bg-indigo-50/40' : '' }}">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $member->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $member->email }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $member->assignedDepartment?->name ?? $member->department ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-700">
                                {{ $member->roles->first()?->name ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $accessCount === 'Full' ? 'Full access' : $accessCount.' permissions' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $member->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $member->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                            @can('staff.update')
                                <button type="button" wire:click="editAccess({{ $member->id }})" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                    Manage access
                                </button>
                                @if ($member->id !== auth()->id())
                                    <button type="button" wire:click="toggleActive({{ $member->id }})" class="text-xs text-slate-500 hover:text-slate-800">
                                        {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $staff->links() }}</div>
    </div>
</div>
