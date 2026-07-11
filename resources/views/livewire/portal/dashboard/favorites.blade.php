<?php

use App\Domain\Content\Models\Favorite;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public function remove(int $id): void
    {
        Favorite::query()
            ->where('customer_id', auth('guest')->id())
            ->whereKey($id)
            ->delete();
    }

    public function with(): array
    {
        return [
            'favorites' => Favorite::query()
                ->with('favoritable')
                ->where('customer_id', auth('guest')->id())
                ->latest()
                ->get(),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-4xl text-stone-900">{{ __('portal.dashboard.favorites') }}</h1>
        <div class="mt-8 space-y-4">
            @forelse($favorites as $favorite)
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm flex items-center justify-between gap-3">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-stone-400">{{ class_basename($favorite->favoritable_type) }}</div>
                        <div class="font-semibold text-stone-900">{{ $favorite->favoritable->name ?? $favorite->favoritable->title ?? 'Saved item' }}</div>
                    </div>
                    <button type="button" wire:click="remove({{ $favorite->id }})" class="text-sm text-red-600">Remove</button>
                </div>
            @empty
                <p class="text-stone-500">{{ __('portal.dashboard.empty') }}</p>
            @endforelse
        </div>
    </div>
</div>
