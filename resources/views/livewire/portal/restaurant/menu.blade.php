<?php

use App\Domain\Restaurant\Models\MenuCategory;
use App\Domain\Restaurant\Models\MenuItem;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public ?int $categoryId = null;

    public function addToCart(int $menuItemId): void
    {
        $item = MenuItem::query()->where('is_available', true)->findOrFail($menuItemId);
        $cart = session('portal.fnb_cart', []);

        if (isset($cart[$item->id])) {
            $cart[$item->id]['quantity']++;
        } else {
            $cart[$item->id] = [
                'menu_item_id' => $item->id,
                'name' => $item->name,
                'price' => (float) $item->price,
                'quantity' => 1,
            ];
        }

        session(['portal.fnb_cart' => $cart]);
        session()->flash('cart_status', __('portal.restaurant.add').': '.$item->name);
    }

    public function with(): array
    {
        $categories = MenuCategory::query()
            ->where('is_active', true)
            ->with(['items' => fn ($q) => $q->where('is_available', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $items = MenuItem::query()
            ->where('is_available', true)
            ->when($this->categoryId, fn ($q) => $q->where('menu_category_id', $this->categoryId))
            ->orderBy('sort_order')
            ->get();

        return [
            'categories' => $categories,
            'items' => $items,
            'cartCount' => collect(session('portal.fnb_cart', []))->sum('quantity'),
            'currency' => app(\App\Domain\Properties\Services\PropertyContext::class)->property()?->currency ?? 'USD',
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-[0.25em] text-amber-700/80">{{ __('portal.nav.restaurant') }}</p>
                <h1 class="mt-2 font-display text-4xl text-stone-900 sm:text-5xl">{{ __('portal.restaurant.menu') }}</h1>
            </div>
            <a href="{{ route('portal.restaurant.cart') }}" wire:navigate class="portal-btn">
                {{ __('portal.restaurant.cart') }} ({{ $cartCount }})
            </a>
        </div>

        @if(session('cart_status'))
            <div class="mt-6 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('cart_status') }}</div>
        @endif

        <div class="mt-8 flex flex-wrap gap-2">
            <button type="button" wire:click="$set('categoryId', null)" class="rounded-full px-4 py-2 text-sm font-semibold {{ $categoryId === null ? 'bg-stone-900 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200' }}">All</button>
            @foreach($categories as $category)
                <button type="button" wire:click="$set('categoryId', {{ $category->id }})" class="rounded-full px-4 py-2 text-sm font-semibold {{ $categoryId === $category->id ? 'bg-stone-900 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200' }}">
                    {{ $category->name }}
                </button>
            @endforeach
        </div>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($items as $item)
                <article class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
                    @php $photo = $item->getFirstMediaUrl('gallery'); @endphp
                    @if($photo)
                        <img src="{{ $photo }}" alt="" class="aspect-[4/3] w-full object-cover">
                    @endif
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="font-display text-2xl text-stone-900">{{ $item->name }}</h2>
                            <div class="font-semibold text-stone-800">{{ $currency }} {{ number_format($item->price, 2) }}</div>
                        </div>
                        <p class="mt-2 text-sm text-stone-600">{{ $item->description }}</p>
                        <button type="button" wire:click="addToCart({{ $item->id }})" class="portal-btn mt-5">{{ __('portal.restaurant.add') }}</button>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-stone-300 bg-white px-8 py-16 text-center text-stone-500">
                    Menu coming soon.
                </div>
            @endforelse
        </div>

        <div class="mt-10">
            <a href="{{ route('portal.restaurant.reserve') }}" wire:navigate class="text-sm font-semibold text-amber-800 underline">{{ __('portal.restaurant.table_reserve') }}</a>
        </div>
    </div>
</div>
