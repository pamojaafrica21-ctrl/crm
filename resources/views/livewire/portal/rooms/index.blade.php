<?php

use App\Domain\Rooms\Models\RoomType;
use App\Domain\Rooms\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    #[Url]
    public string $check_in = '';

    #[Url]
    public string $check_out = '';

    #[Url]
    public int $adults = 1;

    #[Url]
    public int $children = 0;

    #[Url]
    public ?int $room_type_id = null;

    #[Url]
    public ?float $min_price = null;

    #[Url]
    public ?float $max_price = null;

    public function mount(): void
    {
        if ($this->check_in === '') {
            $this->check_in = now()->addDay()->toDateString();
        }
        if ($this->check_out === '') {
            $this->check_out = now()->addDays(2)->toDateString();
        }
    }

    public function search(): void
    {
        $this->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1|max:20',
            'children' => 'required|integer|min:0|max:20',
            'room_type_id' => 'nullable|integer',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
        ]);
    }

    public function with(): array
    {
        $availability = app(RoomAvailabilityService::class);
        $rooms = collect();

        try {
            if ($this->check_in && $this->check_out && Carbon::parse($this->check_out)->gt(Carbon::parse($this->check_in))) {
                $rooms = $availability->search(
                    $this->check_in,
                    $this->check_out,
                    $this->adults,
                    $this->children,
                    $this->room_type_id,
                    $this->min_price,
                    $this->max_price,
                );
            }
        } catch (\Throwable) {
            $rooms = collect();
        }

        return [
            'rooms' => $rooms,
            'roomTypes' => RoomType::query()->where('is_active', true)->orderBy('name')->get(),
            'currency' => app(\App\Domain\Properties\Services\PropertyContext::class)->property()?->currency ?? 'USD',
            'nights' => max(1, Carbon::parse($this->check_in)->diffInDays(Carbon::parse($this->check_out))),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-10">
            <p class="text-sm uppercase tracking-[0.25em] text-amber-700/80">{{ __('portal.nav.rooms') }}</p>
            <h1 class="mt-2 font-display text-4xl text-stone-900 sm:text-5xl">{{ __('portal.rooms.search') }}</h1>
        </div>

        <form wire:submit="search" class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">{{ __('portal.rooms.check_in') }}</label>
                    <input type="date" wire:model="check_in" class="portal-input">
                    @error('check_in') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">{{ __('portal.rooms.check_out') }}</label>
                    <input type="date" wire:model="check_out" class="portal-input">
                    @error('check_out') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">{{ __('portal.rooms.adults') }}</label>
                    <input type="number" min="1" wire:model="adults" class="portal-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">{{ __('portal.rooms.children') }}</label>
                    <input type="number" min="0" wire:model="children" class="portal-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">{{ __('portal.rooms.room_type') }}</label>
                    <select wire:model="room_type_id" class="portal-input">
                        <option value="">All</option>
                        @foreach($roomTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Min price</label>
                    <input type="number" step="0.01" min="0" wire:model="min_price" class="portal-input" placeholder="0">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Max price</label>
                    <input type="number" step="0.01" min="0" wire:model="max_price" class="portal-input" placeholder="Any">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="portal-btn w-full">{{ __('portal.rooms.search') }}</button>
                </div>
            </div>
        </form>

        <div class="mt-10 space-y-6">
            @forelse($rooms as $room)
                <article class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm lg:grid lg:grid-cols-3">
                    <div class="aspect-[4/3] bg-stone-200 lg:aspect-auto">
                        @php $photo = $room->getFirstMediaUrl('gallery'); @endphp
                        @if($photo)
                            <img src="{{ $photo }}" alt="{{ $room->name }}" class="h-full w-full object-cover">
                        @else
                            <img src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1200&q=80" alt="" class="h-full w-full object-cover">
                        @endif
                    </div>
                    <div class="p-6 lg:col-span-2 lg:flex lg:flex-col lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="font-display text-3xl text-stone-900">{{ $room->name }}</h2>
                                    <p class="mt-1 text-sm text-stone-500">{{ __('portal.rooms.capacity', ['count' => $room->totalCapacity()]) }} · {{ $room->bed_type }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="font-display text-3xl text-stone-900">{{ $currency }} {{ number_format($room->base_price, 0) }}</div>
                                    <div class="text-xs uppercase tracking-wide text-stone-500">{{ __('portal.rooms.per_night') }}</div>
                                </div>
                            </div>
                            <p class="mt-4 text-stone-600">{{ \Illuminate\Support\Str::limit($room->description, 180) }}</p>
                            @if($room->amenities->isNotEmpty())
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($room->amenities->take(6) as $amenity)
                                        <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-700">{{ $amenity->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-800">
                                {{ __('portal.rooms.available') }} · {{ $room->available_count }}
                            </span>
                            <div class="flex gap-3">
                                <a href="{{ route('portal.rooms.show', $room) }}?check_in={{ $check_in }}&check_out={{ $check_out }}&adults={{ $adults }}&children={{ $children }}" wire:navigate class="rounded-full border border-stone-300 px-5 py-2.5 text-sm font-semibold text-stone-800 hover:bg-stone-50">
                                    {{ __('portal.rooms.view_details') }}
                                </a>
                                <a href="{{ route('portal.booking.create', ['room_type_id' => $room->id, 'check_in' => $check_in, 'check_out' => $check_out, 'adults' => $adults, 'children' => $children]) }}" wire:navigate class="portal-btn">
                                    {{ __('portal.rooms.book') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-stone-300 bg-white px-8 py-16 text-center">
                    <p class="text-stone-600">{{ __('portal.rooms.no_results') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
