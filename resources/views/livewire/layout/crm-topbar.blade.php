<?php

use App\Domain\Properties\Services\PropertyContext;
use Livewire\Volt\Component;

new class extends Component
{
    public function properties()
    {
        return app(PropertyContext::class)->availableForUser(auth()->user());
    }

    public function activeProperty()
    {
        return app(PropertyContext::class)->property();
    }

    public function switchProperty(int $propertyId): void
    {
        if (! auth()->user()->hasPropertyAccess($propertyId)) {
            return;
        }

        app(PropertyContext::class)->set($propertyId);
        $this->redirect(request()->header('Referer', route('dashboard')), navigate: true);
    }

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(route('login'), navigate: true);
    }
}; ?>

<header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between shrink-0">
    <div class="flex items-center gap-4">
        @if ($this->activeProperty())
            @if ($this->properties()->count() > 1)
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 uppercase tracking-wide">Property</span>
                    <select wire:change="switchProperty($event.target.value)"
                            class="text-sm font-medium border-slate-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach ($this->properties() as $property)
                            <option value="{{ $property->id }}" @selected($property->id === $this->activeProperty()?->id)>
                                {{ $property->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="text-sm font-medium text-slate-800">{{ $this->activeProperty()->name }}</div>
            @endif
        @endif
    </div>

    <div class="flex items-center gap-4">
        <livewire:notifications.notification-bell />

        <div class="flex items-center gap-3">
            <div class="text-right">
                <div class="text-sm font-medium text-slate-800">{{ auth()->user()->name }}</div>
                <div class="text-xs text-slate-500">{{ auth()->user()->roles->first()?->name }}</div>
            </div>
            <button wire:click="logout" class="text-sm text-slate-500 hover:text-slate-800 px-3 py-1.5 rounded-lg hover:bg-slate-100">
                Log out
            </button>
        </div>
    </div>
</header>
