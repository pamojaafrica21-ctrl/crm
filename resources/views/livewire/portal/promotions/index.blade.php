<?php

use App\Domain\Content\Models\Promotion;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public function with(): array
    {
        return [
            'promotions' => Promotion::query()
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->latest()
                ->get(),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-4xl text-stone-900 sm:text-5xl">{{ __('portal.nav.promotions') }}</h1>
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($promotions as $promo)
                <article class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
                    @if($promo->image_url)
                        <img src="{{ $promo->image_url }}" alt="" class="aspect-[16/10] w-full object-cover">
                    @endif
                    <div class="p-6">
                        <h2 class="font-display text-2xl text-stone-900">{{ $promo->title }}</h2>
                        <p class="mt-3 text-stone-600">{{ $promo->description }}</p>
                        @if($promo->discount_percent)
                            <div class="mt-4 text-sm font-semibold text-amber-800">{{ number_format($promo->discount_percent, 0) }}% off</div>
                        @endif
                    </div>
                </article>
            @empty
                <p class="col-span-full text-stone-500">No promotions right now.</p>
            @endforelse
        </div>
    </div>
</div>
