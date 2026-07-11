<?php

use App\Application\Restaurant\CreateTableReservationAction;
use App\Domain\Restaurant\Models\RestaurantTable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public string $reserved_at = '';

    public int $party_size = 2;

    public ?int $restaurant_table_id = null;

    public string $special_requests = '';

    public function mount(): void
    {
        if (! Auth::guard('guest')->check()) {
            $this->redirect(route('guest.login', ['redirect' => route('portal.restaurant.reserve')]), navigate: true);

            return;
        }

        $this->reserved_at = now()->addDay()->setTime(19, 0)->format('Y-m-d\TH:i');
    }

    public function submit(CreateTableReservationAction $action): void
    {
        $this->validate([
            'reserved_at' => 'required|date|after:now',
            'party_size' => 'required|integer|min:1|max:20',
            'restaurant_table_id' => 'nullable|integer',
            'special_requests' => 'nullable|string|max:2000',
        ]);

        try {
            $reservation = $action->execute(
                Auth::guard('guest')->user(),
                $this->reserved_at,
                $this->party_size,
                $this->restaurant_table_id,
                $this->special_requests ?: null,
            );

            session()->flash('status', 'Table reservation '.$reservation->confirmation_number.' received.');
            $this->redirect(route('portal.dashboard.reservations'), navigate: true);
        } catch (\Throwable $e) {
            $this->addError('reserved_at', $e->getMessage());
        }
    }

    public function with(): array
    {
        return [
            'tables' => RestaurantTable::query()
                ->where('is_active', true)
                ->where('capacity', '>=', $this->party_size)
                ->orderBy('name')
                ->get(),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-20">
    <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-4xl text-stone-900">{{ __('portal.restaurant.table_reserve') }}</h1>

        <form wire:submit="submit" class="mt-8 space-y-4 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Date & time</label>
                <input type="datetime-local" wire:model="reserved_at" class="portal-input">
                @error('reserved_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Party size</label>
                <input type="number" min="1" wire:model.live="party_size" class="portal-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Preferred table</label>
                <select wire:model="restaurant_table_id" class="portal-input">
                    <option value="">Any available</option>
                    @foreach($tables as $table)
                        <option value="{{ $table->id }}">{{ $table->name }} (seats {{ $table->capacity }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">{{ __('portal.booking.special_requests') }}</label>
                <textarea wire:model="special_requests" rows="3" class="portal-input"></textarea>
            </div>
            <button type="submit" class="portal-btn w-full">Reserve</button>
        </form>
    </div>
</div>
