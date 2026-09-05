<?php

use App\Domain\Tasks\Models\Task;
use App\Domain\Tasks\Models\TaskComment;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public Task $task;
    public string $commentBody = '';
    public $attachment;

    public function mount(Task $task): void
    {
        $this->authorize('tasks.view');
        $this->task = $task->load(['assignee', 'assignees', 'departments', 'creator', 'customer', 'comments.user']);
    }

    public function addComment(): void
    {
        $this->authorize('tasks.update');
        $this->validate(['commentBody' => 'required|string']);

        TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => auth()->id(),
            'body' => $this->commentBody,
        ]);

        $this->commentBody = '';
        $this->task->load('comments.user');
    }

    public function uploadAttachment(): void
    {
        $this->authorize('tasks.update');
        $this->validate(['attachment' => 'file|max:10240']);
        $this->task->addMedia($this->attachment->getRealPath())
            ->usingFileName($this->attachment->getClientOriginalName())
            ->toMediaCollection('attachments');
        $this->attachment = null;
    }
}; ?>

<div class="max-w-2xl space-y-6">
        <div>
            <a href="{{ route('tasks.index') }}" wire:navigate class="text-sm text-indigo-600">← Tasks</a>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $task->title }}</h1>
            <div class="flex gap-2 mt-2 text-xs">
                <span class="px-2 py-0.5 bg-slate-100 rounded-full">{{ $task->status->label() }}</span>
                <span class="px-2 py-0.5 bg-slate-100 rounded-full">{{ $task->priority->label() }}</span>
            </div>
            <div class="text-sm text-slate-500 mt-2">
                Assigned:
                {{ $task->assignees->isNotEmpty() ? $task->assignees->pluck('name')->join(', ') : ($task->assignee?->name ?? 'Unassigned') }}
                @if ($task->departments->isNotEmpty())
                    · Depts: {{ $task->departments->pluck('name')->join(', ') }}
                @endif
            </div>
        </div>
        @if ($task->description)
            <div class="bg-white rounded-xl p-4 border border-slate-100 text-sm text-slate-700">{{ $task->description }}</div>
        @endif
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <h2 class="font-semibold mb-4">Comments</h2>
            @can('tasks.update')
                <form wire:submit="addComment" class="mb-4">
                    <textarea wire:model="commentBody" rows="2" class="w-full rounded-lg border-slate-200 text-sm" placeholder="Add comment..."></textarea>
                    <button type="submit" class="mt-2 px-3 py-1.5 bg-indigo-600 text-white text-xs rounded-lg">Comment</button>
                </form>
                <form wire:submit="uploadAttachment" class="mb-4 flex gap-2">
                    <input type="file" wire:model="attachment" class="text-sm">
                    <button type="submit" class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg">Upload</button>
                </form>
            @endcan
            @foreach ($task->comments->sortByDesc('created_at') as $comment)
                <div class="py-3 border-b border-slate-50">
                    <div class="text-sm">{{ $comment->body }}</div>
                    <div class="text-xs text-slate-400 mt-1">{{ $comment->user->name }} · {{ $comment->created_at->diffForHumans() }}</div>
                </div>
            @endforeach
            @foreach ($task->getMedia('attachments') as $media)
                <div class="text-sm py-1">📎 {{ $media->file_name }}</div>
            @endforeach
        </div>
    </div>
