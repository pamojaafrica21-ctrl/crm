<?php

use Livewire\Volt\Component;

new class extends Component
{
    public function notifications()
    {
        return auth()->user()->unreadNotifications()->latest()->limit(10)->get();
    }

    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }
}; ?>

<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" class="relative p-2 text-slate-500 hover:text-slate-800 rounded-lg hover:bg-slate-100">
        🔔
        @if ($this->unreadCount() > 0)
            <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                {{ min($this->unreadCount(), 9) }}{{ $this->unreadCount() > 9 ? '+' : '' }}
            </span>
        @endif
    </button>

    <div x-show="open" @click.outside="open = false"
         class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-slate-200 z-50">
        <div class="p-3 border-b border-slate-100 flex justify-between items-center">
            <span class="font-medium text-sm">Notifications</span>
            @if ($this->unreadCount() > 0)
                <button wire:click="markAllRead" class="text-xs text-indigo-600 hover:text-indigo-800">Mark all read</button>
            @endif
        </div>
        <div class="max-h-64 overflow-y-auto">
            @forelse ($this->notifications() as $notification)
                <div wire:click="markAsRead('{{ $notification->id }}')"
                     class="p-3 border-b border-slate-50 hover:bg-slate-50 cursor-pointer {{ $notification->read_at ? 'opacity-60' : '' }}">
                    <div class="text-sm font-medium text-slate-800">{{ $notification->data['title'] ?? 'Notification' }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">{{ $notification->data['message'] ?? '' }}</div>
                    <div class="text-xs text-slate-400 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                </div>
            @empty
                <div class="p-4 text-sm text-slate-500 text-center">No notifications</div>
            @endforelse
        </div>
    </div>
</div>
