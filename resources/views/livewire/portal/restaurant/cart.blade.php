<?php

use App\Application\Restaurant\PlaceFnbOrderAction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public array $cart = [];

    public string $order_type = 'dine_in';

    public string $special_requests = '';

    public function mount(): void
    {
        $this->cart = session('portal.fnb_cart', []);
    }

    public function updateQty(int $id, int $qty): void
    {
        if (! isset($this->cart[$id])) {
            return;
        }

        if ($qty < 1) {
            unset($this->cart[$id]);
        } else {
            $this->cart[$id]['quantity'] = $qty;
        }

        session(['portal.fnb_cart' => $this->cart]);
    }

    public function remove(int $id): void
    {
        unset($this->cart[$id]);
        session(['portal.fnb_cart' => $this->cart]);
    }

    public function checkout(PlaceFnbOrderAction $action): void
    {
        if (! Auth::guard('guest')->check()) {
            $this->redirect(route('guest.login', ['redirect' => route('portal.restaurant.cart')]), navigate: true);

            return;
        }

        if ($this->cart === []) {
            $this->addError('cart', __('portal.restaurant.empty_cart'));

            return;
        }

        $this->validate([
            'order_type' => 'required|in:dine_in,takeaway,room_service',
            'special_requests' => 'nullable|string|max:2000',
        ]);

        $items = collect($this->cart)->map(fn ($row) => [
            'menu_item_id' => (int) $row['menu_item_id'],
            'quantity' => (int) $row['quantity'],
        ])->values()->all();

        try {
            $order = $action->execute(
                Auth::guard('guest')->user(),
                $items,
                $this->order_type,
                $this->special_requests ?: null,
            );

            session()->forget('portal.fnb_cart');
            session()->flash('status', __('portal.restaurant.order_placed'));
            $this->redirect(route('portal.dashboard.orders'), navigate: true);
        } catch (\Throwable $e) {
            $this->addError('cart', $e->getMessage());
        }
    }

    public function with(): array
    {
        $subtotal = collect($this->cart)->sum(fn ($row) => $row['price'] * $row['quantity']);
        $taxRate = (float) config('portal.tax_rate', 10) / 100;
        $tax = round($subtotal * $taxRate, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => $tax,
            'total' => round($subtotal + $tax, 2),
            'currency' => app(\App\Domain\Properties\Services\PropertyContext::class)->property()?->currency ?? 'USD',
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-20">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-4xl text-stone-900">{{ __('portal.restaurant.cart') }}</h1>

        <div class="mt-8 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            @forelse($cart as $id => $item)
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 py-4" wire:key="fnb-{{ $id }}">
                    <div>
                        <div class="font-semibold text-stone-900">{{ $item['name'] }}</div>
                        <div class="text-sm text-stone-500">{{ $currency }} {{ number_format($item['price'], 2) }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="number" min="1" value="{{ $item['quantity'] }}" wire:change="updateQty({{ $id }}, parseInt($event.target.value) || 1)" class="portal-input w-20">
                        <button type="button" wire:click="remove({{ $id }})" class="text-sm text-red-600">Remove</button>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-stone-500">{{ __('portal.restaurant.empty_cart') }}</p>
            @endforelse

            @error('cart') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror

            @if($cart !== [])
                <div class="mt-6 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-stone-700">Order type</label>
                        <select wire:model="order_type" class="portal-input">
                            <option value="dine_in">Dine in</option>
                            <option value="takeaway">Takeaway</option>
                            <option value="room_service">{{ __('portal.restaurant.room_service') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-stone-700">{{ __('portal.booking.special_requests') }}</label>
                        <textarea wire:model="special_requests" rows="2" class="portal-input"></textarea>
                    </div>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-stone-500">Subtotal</dt><dd>{{ $currency }} {{ number_format($subtotal, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-stone-500">{{ __('portal.booking.tax') }}</dt><dd>{{ $currency }} {{ number_format($tax, 2) }}</dd></div>
                        <div class="flex justify-between border-t border-stone-100 pt-2 font-semibold"><dt>{{ __('portal.booking.grand_total') }}</dt><dd>{{ $currency }} {{ number_format($total, 2) }}</dd></div>
                    </dl>
                    <button type="button" wire:click="checkout" class="portal-btn w-full">{{ __('portal.restaurant.checkout') }}</button>
                </div>
            @endif
        </div>

        <a href="{{ route('portal.restaurant.menu') }}" wire:navigate class="mt-6 inline-block text-sm font-semibold text-amber-800">← {{ __('portal.restaurant.menu') }}</a>
    </div>
</div>
