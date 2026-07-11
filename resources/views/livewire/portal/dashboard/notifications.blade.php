<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public function markRead(string $id): void
    {
        $notification = auth('guest')->user()->notifications()->whereKey($id)->first();
        $notification?->markAsRead();
    }

    public function with(): array
    {
        return [
            'notifications' => auth('guest')->user()->notifications()->latest()->limit(50)->get(),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-4xl text-stone-900">Notifications</h1>
        <div class="mt-8 space-y-3">
            @forelse($notifications as $notification)
                <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm {{ $notification->read_at ? 'opacity-70' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold text-stone-900">{{ $notification->data['title'] ?? 'Notification' }}</div>
                            <p class="mt-1 text-sm text-stone-600">{{ $notification->data['message'] ?? '' }}</p>
                            <div class="mt-2 text-xs text-stone-400">{{ $notification->created_at->diffForHumans() }}</div>
                        </div>
                        @if(! $notification->read_at)
                            <button type="button" wire:click="markRead('{{ $notification->id }}')" class="text-xs font-semibold text-amber-800">Mark read</button>
                        @endif
                    </div>
                    @if(! empty($notification->data['action_url']))
                        <a href="{{ $notification->data['action_url'] }}" class="mt-3 inline-block text-sm font-semibold text-amber-800">View</a>
                    @endif
                </div>
            @empty
                <p class="text-stone-500">{{ __('portal.dashboard.empty') }}</p>
            @endforelse
        </div>
    </div>
</div>
