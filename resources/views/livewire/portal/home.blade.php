<?php

use App\Domain\Properties\Models\Property;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public function with(): array
    {
        /** @var Property $property */
        $property = request()->attributes->get('portal_property')
            ?? app(\App\Domain\Properties\Services\PropertyContext::class)->property();

        return [
            'property' => $property,
            'featuredRooms' => class_exists(\App\Domain\Rooms\Models\RoomType::class)
                ? \App\Domain\Rooms\Models\RoomType::query()
                    ->where('is_featured', true)
                    ->where('is_active', true)
                    ->limit(3)
                    ->get()
                : collect(),
            'promotions' => class_exists(\App\Domain\Content\Models\Promotion::class)
                ? \App\Domain\Content\Models\Promotion::query()
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                    })
                    ->limit(3)
                    ->get()
                : collect(),
        ];
    }
}; ?>

<div>
    <section class="relative min-h-[100svh] overflow-hidden">
        <div class="absolute inset-0">
            @if($property->hero_image)
                <img src="{{ $property->hero_image }}" alt="" class="h-full w-full object-cover">
            @else
                <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=2000&q=80" alt="" class="h-full w-full object-cover">
            @endif
            <div class="absolute inset-0 bg-gradient-to-b from-stone-950/55 via-stone-950/35 to-stone-950/80"></div>
        </div>

        <div class="relative mx-auto flex min-h-[100svh] max-w-7xl flex-col justify-end px-4 pb-24 pt-32 sm:px-6 lg:px-8 lg:pb-32">
            <p class="mb-4 text-sm font-medium uppercase tracking-[0.35em] text-amber-200/90 animate-[fadeIn_0.8s_ease-out]">
                {{ $property->tagline ?? __('portal.brand') }}
            </p>
            <h1 class="max-w-3xl font-display text-5xl leading-none text-white sm:text-6xl lg:text-7xl animate-[fadeIn_1s_ease-out]">
                {{ $property->name }}
            </h1>
            <p class="mt-6 max-w-xl text-lg text-stone-200/90 animate-[fadeIn_1.2s_ease-out]">
                {{ \Illuminate\Support\Str::limit($property->about ?? 'An elevated stay shaped by calm spaces, thoughtful service, and memorable dining.', 160) }}
            </p>
            <div class="mt-10 flex flex-wrap gap-4 animate-[fadeIn_1.4s_ease-out]">
                @if(Route::has('portal.rooms.index'))
                    <a href="{{ route('portal.rooms.index') }}" wire:navigate class="portal-btn">{{ __('portal.home.hero_cta') }}</a>
                @else
                    <a href="{{ route('guest.register') }}" wire:navigate class="portal-btn">{{ __('portal.nav.register') }}</a>
                @endif
                @if(Route::has('portal.restaurant.menu'))
                    <a href="{{ route('portal.restaurant.menu') }}" wire:navigate class="rounded-full border border-white/40 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                        {{ __('portal.home.view_menu') }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    <section class="portal-section">
        <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
            <div>
                <h2 class="font-display text-4xl text-stone-900 sm:text-5xl">{{ $property->name }}</h2>
                <p class="mt-6 text-lg leading-relaxed text-stone-600">{{ $property->about }}</p>
                <dl class="mt-8 grid gap-4 sm:grid-cols-2 text-sm">
                    @if($property->check_in_time)
                        <div class="rounded-2xl bg-white p-4 shadow-sm border border-stone-100">
                            <dt class="text-stone-500">Check-in</dt>
                            <dd class="mt-1 font-semibold text-stone-900">{{ \Illuminate\Support\Str::of($property->check_in_time)->substr(0, 5) }}</dd>
                        </div>
                    @endif
                    @if($property->check_out_time)
                        <div class="rounded-2xl bg-white p-4 shadow-sm border border-stone-100">
                            <dt class="text-stone-500">Check-out</dt>
                            <dd class="mt-1 font-semibold text-stone-900">{{ \Illuminate\Support\Str::of($property->check_out_time)->substr(0, 5) }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
            <div class="relative overflow-hidden rounded-[2rem] shadow-2xl shadow-stone-900/10">
                <img src="https://images.unsplash.com/photo-1582719508461-905c673771fd?auto=format&fit=crop&w=1200&q=80" alt="" class="aspect-[4/5] w-full object-cover">
            </div>
        </div>
    </section>

    @if($featuredRooms->isNotEmpty())
        <section class="bg-stone-100/80">
            <div class="portal-section">
                <div class="flex items-end justify-between gap-4">
                    <h2 class="font-display text-4xl text-stone-900">{{ __('portal.home.featured_rooms') }}</h2>
                    <a href="{{ route('portal.rooms.index') }}" wire:navigate class="text-sm font-semibold text-amber-700 hover:text-amber-800">{{ __('portal.home.explore_rooms') }}</a>
                </div>
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach($featuredRooms as $room)
                        <a href="{{ route('portal.rooms.show', $room) }}" wire:navigate class="group overflow-hidden rounded-3xl bg-white shadow-sm border border-stone-100 transition hover:-translate-y-1 hover:shadow-lg">
                            <div class="aspect-[4/3] overflow-hidden bg-stone-200">
                                @if($room->getFirstMediaUrl('gallery'))
                                    <img src="{{ $room->getFirstMediaUrl('gallery') }}" alt="" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                                @else
                                    <img src="https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=800&q=80" alt="" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                                @endif
                            </div>
                            <div class="p-6">
                                <h3 class="font-display text-2xl text-stone-900">{{ $room->name }}</h3>
                                <p class="mt-2 text-sm text-stone-500 line-clamp-2">{{ $room->description }}</p>
                                <p class="mt-4 font-semibold text-stone-900">{{ number_format($room->base_price, 2) }} {{ $property->currency }} <span class="font-normal text-stone-500">{{ __('portal.rooms.per_night') }}</span></p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="portal-section">
        <div class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
            <div class="overflow-hidden rounded-[2rem]">
                <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1400&q=80" alt="" class="aspect-[16/10] w-full object-cover">
            </div>
            <div>
                <h2 class="font-display text-4xl text-stone-900">{{ __('portal.home.restaurant') }}</h2>
                <p class="mt-4 text-stone-600 leading-relaxed">Seasonal plates, craft cocktails, and room service whenever you prefer to stay in.</p>
                @if(Route::has('portal.restaurant.menu'))
                    <a href="{{ route('portal.restaurant.menu') }}" wire:navigate class="portal-btn mt-8">{{ __('portal.home.view_menu') }}</a>
                @endif
            </div>
        </div>
    </section>

    @if($promotions->isNotEmpty())
        <section class="bg-stone-950 text-white">
            <div class="portal-section">
                <h2 class="font-display text-4xl">{{ __('portal.home.promotions') }}</h2>
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach($promotions as $promo)
                        <article class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                            <h3 class="font-display text-2xl">{{ $promo->title }}</h3>
                            <p class="mt-3 text-sm text-stone-300">{{ \Illuminate\Support\Str::limit(strip_tags($promo->description), 120) }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="portal-section">
        <h2 class="font-display text-4xl text-stone-900">{{ __('portal.home.testimonials') }}</h2>
        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach([
                ['quote' => 'Quiet luxury with service that anticipates everything.', 'name' => 'Amelia R.'],
                ['quote' => 'The rooms feel like a private residence above the city.', 'name' => 'James K.'],
                ['quote' => 'Breakfast alone is worth returning for.', 'name' => 'Sofia M.'],
            ] as $t)
                <blockquote class="rounded-3xl border border-stone-100 bg-white p-6 shadow-sm">
                    <p class="font-display text-2xl leading-snug text-stone-800">“{{ $t['quote'] }}”</p>
                    <footer class="mt-6 text-sm font-medium text-stone-500">{{ $t['name'] }}</footer>
                </blockquote>
            @endforeach
        </div>
    </section>

    <section class="border-t border-stone-200 bg-stone-100/60">
        <div class="portal-section grid gap-8 lg:grid-cols-2">
            <div>
                <h2 class="font-display text-4xl text-stone-900">{{ __('portal.home.contact') }}</h2>
                <div class="mt-6 space-y-2 text-stone-600">
                    <p>{{ $property->address }}</p>
                    <p>{{ $property->phone }}</p>
                    <p>{{ $property->email }}</p>
                </div>
                @if(Route::has('portal.contact.index'))
                    <a href="{{ route('portal.contact.index') }}" wire:navigate class="portal-btn mt-8">{{ __('portal.nav.contact') }}</a>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach([
                    'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=600&q=80',
                    'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=600&q=80',
                    'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=600&q=80',
                    'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=600&q=80',
                ] as $img)
                    <img src="{{ $img }}" alt="" class="aspect-square w-full rounded-2xl object-cover">
                @endforeach
            </div>
        </div>
    </section>

    <style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    </style>
</div>
