<?php

use App\Domain\Notifications\Models\Announcement;
use App\Domain\Organizations\Models\Department;
use App\Domain\Properties\Services\PropertyContext;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new class extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public string $title = '';
    public string $body = '';
    public array $target_roles = [];
    public array $target_department_ids = [];

    public function with(): array
    {
        $announcements = Announcement::with('creator')->latest()->paginate(10);
        $departmentNames = Department::whereIn(
            'id',
            $announcements->getCollection()->pluck('target_department_ids')->filter()->flatten()->unique()->all()
        )->pluck('name', 'id');

        return [
            'announcements' => $announcements,
            'departmentNames' => $departmentNames,
        ];
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

    public function departments()
    {
        $query = Department::where('is_active', true)->orderBy('name');

        if (auth()->user()?->organization_id) {
            $query->where('organization_id', auth()->user()->organization_id);
        }

        return $query->get();
    }

    public function save(): void
    {
        $this->authorize('announcements.create');
        $this->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'target_department_ids' => 'array',
            'target_department_ids.*' => 'integer|exists:departments,id',
        ]);

        $announcement = Announcement::create([
            'property_id' => app(PropertyContext::class)->id(),
            'created_by' => auth()->id(),
            'title' => $this->title,
            'body' => $this->body,
            'target_roles' => $this->target_roles ?: null,
            'target_department_ids' => $this->target_department_ids ?: null,
            'published_at' => now(),
        ]);

        $usersQuery = User::where('is_active', true)->where('is_super_admin', false);

        if (auth()->user()?->organization_id) {
            $usersQuery->where('organization_id', auth()->user()->organization_id);
            setPermissionsTeamId(auth()->user()->organization_id);
        }

        $users = $usersQuery->get()->filter(function ($user) {
            if (! empty($this->target_roles) && ! $user->hasAnyRole($this->target_roles)) {
                return false;
            }

            if (! empty($this->target_department_ids) && ! in_array($user->department_id, array_map('intval', $this->target_department_ids), true)) {
                return false;
            }

            return true;
        });

        foreach ($users as $user) {
            \App\Infrastructure\Notifications\CrmNotification::send(
                $user,
                $this->title,
                $this->body,
                route('announcements.index')
            );
        }

        $this->reset(['title', 'body', 'target_roles', 'target_department_ids', 'showForm']);
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Announcements</h1>
        @can('announcements.create')
            <button wire:click="$toggle('showForm')" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg">New Announcement</button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <input type="text" wire:model="title" placeholder="Title *" class="w-full rounded-lg border-slate-200">
            <textarea wire:model="body" rows="3" placeholder="Message *" class="w-full rounded-lg border-slate-200"></textarea>

            <div>
                <span class="text-sm text-slate-500">Target roles <span class="text-slate-400">(leave empty for all)</span></span>
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach ($this->roles() as $role)
                        <label class="flex items-center gap-1 text-sm">
                            <input type="checkbox" wire:model="target_roles" value="{{ $role->name }}" class="rounded border-slate-300 text-indigo-600">
                            {{ $role->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <span class="text-sm text-slate-500">Target departments <span class="text-slate-400">(optional)</span></span>
                <div class="flex flex-wrap gap-2 mt-2">
                    @forelse ($this->departments() as $department)
                        <label class="flex items-center gap-1 text-sm">
                            <input type="checkbox" wire:model="target_department_ids" value="{{ $department->id }}" class="rounded border-slate-300 text-indigo-600">
                            {{ $department->name }}
                        </label>
                    @empty
                        <span class="text-sm text-slate-400">No departments yet.</span>
                    @endforelse
                </div>
            </div>

            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Publish</button>
        </form>
    @endif

    <div class="space-y-3">
        @foreach ($announcements as $announcement)
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                <h3 class="font-semibold text-slate-900">{{ $announcement->title }}</h3>
                <p class="text-sm text-slate-600 mt-1">{{ $announcement->body }}</p>
                <div class="text-xs text-slate-400 mt-2">
                    {{ $announcement->creator->name }} · {{ $announcement->published_at?->diffForHumans() }}
                    @if ($announcement->target_roles)
                        · Roles: {{ implode(', ', $announcement->target_roles) }}
                    @endif
                    @if ($announcement->target_department_ids)
                        · Depts: {{ collect($announcement->target_department_ids)->map(fn ($id) => $departmentNames[$id] ?? null)->filter()->join(', ') }}
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    {{ $announcements->links() }}
</div>
