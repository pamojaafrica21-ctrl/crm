<?php

use App\Domain\Notifications\Models\Announcement;
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

    public function with(): array
    {
        return ['announcements' => Announcement::with('creator')->latest()->paginate(10)];
    }

    public function roles() { return Role::orderBy('name')->get(); }

    public function save(): void
    {
        $this->authorize('announcements.create');
        $this->validate(['title' => 'required|string', 'body' => 'required|string']);

        $announcement = Announcement::create([
            'property_id' => app(PropertyContext::class)->id(),
            'created_by' => auth()->id(),
            'title' => $this->title,
            'body' => $this->body,
            'target_roles' => $this->target_roles ?: null,
            'published_at' => now(),
        ]);

        $users = User::where('is_active', true)->get()->filter(function ($user) {
            if (empty($this->target_roles)) {
                return true;
            }

            return $user->hasAnyRole($this->target_roles);
        });

        foreach ($users as $user) {
            \App\Infrastructure\Notifications\CrmNotification::send(
                $user,
                $this->title,
                $this->body,
                route('announcements.index')
            );
        }

        $this->reset(['title', 'body', 'target_roles', 'showForm']);
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
                <div class="flex flex-wrap gap-2">
                    <span class="text-sm text-slate-500">Target roles (leave empty for all):</span>
                    @foreach ($this->roles() as $role)
                        <label class="flex items-center gap-1 text-sm">
                            <input type="checkbox" wire:model="target_roles" value="{{ $role->name }}"> {{ $role->name }}
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Publish</button>
            </form>
        @endif

        <div class="space-y-3">
            @foreach ($announcements as $announcement)
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                    <h3 class="font-semibold text-slate-900">{{ $announcement->title }}</h3>
                    <p class="text-sm text-slate-600 mt-1">{{ $announcement->body }}</p>
                    <div class="text-xs text-slate-400 mt-2">{{ $announcement->creator->name }} · {{ $announcement->published_at?->diffForHumans() }}</div>
                </div>
            @endforeach
        </div>
        {{ $announcements->links() }}
    </div>
