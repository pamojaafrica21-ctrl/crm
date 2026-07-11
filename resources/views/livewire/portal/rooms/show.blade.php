<?php

use App\Domain\Content\Models\Review;
use App\Domain\Rooms\Models\RoomType;
use App\Domain\Rooms\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public RoomType $roomType;

    public string $check_in = '';

    public string $check_out = '';

    public int $adults = 1;

    public int $children = 0;

    public function mount(RoomType $roomType): void
    {
        $this->roomType = $roomType->load(['amenities', 'media']);
        $this->check_in = request('check_in', now()->addDay()->toDateString());
        $this->check_out = request('check_out', now()->addDays(2)->toDateString());
        $this->adults = (int) request('adults', 1);
        $this->children = (int) request('children', 0);
    }

    public function with(): array
    {
        $availability = app(RoomAvailabilityService::class);
        $from = Carbon::parse($this->check_in)->startOfDay();
        $calendar = $availability->calendarOccupancy($this->roomType, $from, $from->copy()->addDays(30));

        $reviews = class_exists(Review::class)
            ? Review::query()
                ->where('reviewable_type', RoomType::class)
                ->where('reviewable_id', $this->roomType->id)
                ->where('is_approved', true)
                ->latest()
                ->limit(10)
                ->get()
            : collect();

        $similar = RoomType::query()
            ->with('media')
            ->where('is_active', true)
            ->where('id', '!=', $this->roomType->id)
            ->whereBetween('base_price', [
                max(0, (float) $this->roomType->base_price * 0.7),
                (float) $this->roomType->base_price * 1.3,
            ])
            ->limit(3)
            ->get();

        return [
            'calendar' => $calendar,
            'reviews' => $reviews,
            'similar' => $similar,
            'available' => $availability->isAvailable($this->roomType, $this->check_in, $this->check_out),
            'currency' => app(\App\Domain\Properties\Services\PropertyContext::class)->property()?->currency ?? 'USD',
            'photos' => $this->roomType->getMedia('gallery'),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-2">
            <div>
                <div class="overflow-hidden rounded-[2rem] bg-stone-200">
                    @php $hero = $photos->first()?->getUrl() ?? 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1400&q=80'; @endphp
                    <img src="{{ $hero }}" alt="{{ $roomType->name }}" class="aspect-[4/3] w-full object-cover">
                </div>
                @if($photos->count() > 1)
                    <div class="mt-3 grid grid-cols-4 gap-2">
                        @foreach($photos->skip(1)->take(4) as $photo)
                            <img src="{{ $photo->getUrl() }}" alt="" class="aspect-square rounded-xl object-cover">
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <p class="text-sm uppercase tracking-[0.25em] text-amber-700/80">{{ __('portal.nav.rooms') }}</p>
                <h1 class="mt-2 font-display text-5xl text-stone-900">{{ $roomType->name }}</h1>
                <p class="mt-2 text-stone-500">{{ __('portal.rooms.capacity', ['count' => $roomType->totalCapacity()]) }} · {{ $roomType->bed_type }}@if($roomType->size) · {{ $roomType->size }}@endif</p>
                <div class="mt-6 font-display text-4xl text-stone-900">{{ $currency }} {{ number_format($roomType->base_price, 0) }} <span class="text-base font-sans text-stone-500">{{ __('portal.rooms.per_night') }}</span></div>
                <p class="mt-6 leading-relaxed text-stone-600">{{ $roomType->description }}</p>

                @if($roomType->amenities->isNotEmpty())
                    <div class="mt-8">
                        <h2 class="font-display text-2xl text-stone-900">{{ __('portal.rooms.amenities') }}</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($roomType->amenities as $amenity)
                                <span class="rounded-full bg-white px-3 py-1.5 text-sm text-stone-700 shadow-sm ring-1 ring-stone-200">{{ $amenity->name }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-8 flex flex-wrap gap-3">
                    @if($available)
                        <a href="{{ route('portal.booking.create', ['room_type_id' => $roomType->id, 'check_in' => $check_in, 'check_out' => $check_out, 'adults' => $adults, 'children' => $children]) }}" wire:navigate class="portal-btn">{{ __('portal.rooms.book') }}</a>
                    @else
                        <span class="rounded-full bg-stone-200 px-5 py-3 text-sm font-semibold text-stone-600">{{ __('portal.rooms.sold_out') }}</span>
                    @endif
                    <a href="{{ route('portal.rooms.index', ['check_in' => $check_in, 'check_out' => $check_out]) }}" wire:navigate class="rounded-full border border-stone-300 px-5 py-3 text-sm font-semibold text-stone-800">Back to rooms</a>
                </div>
            </div>
        </div>

        <section class="mt-16">
            <h2 class="font-display text-3xl text-stone-900">{{ __('portal.rooms.availability') }}</h2>
            <p class="mt-2 text-sm text-stone-500">Next 30 days from {{ \Carbon\Carbon::parse($check_in)->format('M j, Y') }}</p>
            <div class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-10">
                @foreach($calendar as $date => $day)
                    <div class="rounded-xl px-2 py-3 text-center text-xs {{ $day['sold_out'] ? 'bg-stone-200 text-stone-500' : 'bg-white text-stone-800 ring-1 ring-amber-200' }}">
                        <div class="font-semibold">{{ \Carbon\Carbon::parse($date)->format('M j') }}</div>
                        <div class="mt-1">{{ $day['sold_out'] ? __('portal.rooms.sold_out') : $day['available'] }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        @if($reviews->isNotEmpty())
            <section class="mt-16">
                <h2 class="font-display text-3xl text-stone-900">{{ __('portal.rooms.reviews') }}</h2>
                <div class="mt-6 space-y-4">
                    @foreach($reviews as $review)
                        <div class="rounded-2xl border border-stone-200 bg-white p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-semibold text-stone-900">{{ $review->title ?: 'Guest review' }}</div>
                                <div class="text-amber-700">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
                            </div>
                            <p class="mt-2 text-stone-600">{{ $review->body }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if($similar->isNotEmpty())
            <section class="mt-16">
                <h2 class="font-display text-3xl text-stone-900">{{ __('portal.rooms.similar') }}</h2>
                <div class="mt-6 grid gap-6 md:grid-cols-3">
                    @foreach($similar as $item)
                        <a href="{{ route('portal.rooms.show', $item) }}" wire:navigate class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm transition hover:border-amber-300">
                            @php $img = $item->getFirstMediaUrl('gallery') ?: 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=800&q=80'; @endphp
                            <img src="{{ $img }}" alt="" class="aspect-[4/3] w-full object-cover">
                            <div class="p-5">
                                <div class="font-display text-2xl text-stone-900">{{ $item->name }}</div>
                                <div class="mt-1 text-sm text-stone-500">{{ $currency }} {{ number_format($item->base_price, 0) }} {{ __('portal.rooms.per_night') }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
