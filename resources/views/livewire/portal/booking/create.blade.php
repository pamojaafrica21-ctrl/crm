<?php

use App\Application\Bookings\CreateReservationAction;
use App\Application\Content\ApplyCouponAction;
use App\Domain\Rooms\Models\ExtraService;
use App\Domain\Rooms\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public string $check_in = '';

    public string $check_out = '';

    public int $adults = 1;

    public int $children = 0;

    public string $special_requests = '';

    public string $coupon_code = '';

    public float $discount_amount = 0;

    /** @var array<int, array{room_type_id:int, quantity:int, name:string, price:float}> */
    public array $cart = [];

    /** @var array<int, int> extra_service_id => quantity */
    public array $extraQty = [];

    public function mount(): void
    {
        if (! Auth::guard('guest')->check()) {
            $this->redirect(route('guest.login', [
                'redirect' => url()->full(),
            ]), navigate: true);

            return;
        }

        $this->check_in = request('check_in', session('portal.booking.check_in', now()->addDay()->toDateString()));
        $this->check_out = request('check_out', session('portal.booking.check_out', now()->addDays(2)->toDateString()));
        $this->adults = (int) request('adults', session('portal.booking.adults', 1));
        $this->children = (int) request('children', session('portal.booking.children', 0));
        $this->cart = session('portal.booking.cart', []);
        $this->extraQty = session('portal.booking.extras', []);
        $this->coupon_code = session('portal.booking.coupon_code', '');
        $this->discount_amount = (float) session('portal.booking.discount_amount', 0);
        $this->special_requests = session('portal.booking.special_requests', '');

        if ($roomTypeId = request('room_type_id')) {
            $this->addRoom((int) $roomTypeId);
        }

        $this->persist();
    }

    public function addRoom(int $roomTypeId): void
    {
        $type = RoomType::query()->where('is_active', true)->findOrFail($roomTypeId);

        foreach ($this->cart as $index => $item) {
            if ((int) $item['room_type_id'] === $type->id) {
                $this->cart[$index]['quantity']++;
                $this->persist();

                return;
            }
        }

        $this->cart[] = [
            'room_type_id' => $type->id,
            'quantity' => 1,
            'name' => $type->name,
            'price' => (float) $type->base_price,
        ];
        $this->persist();
    }

    public function removeRoom(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        $this->persist();
    }

    public function updateQuantity(int $index, int $quantity): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        if ($quantity < 1) {
            $this->removeRoom($index);

            return;
        }

        $this->cart[$index]['quantity'] = $quantity;
        $this->persist();
    }

    public function updatedExtraQty(): void
    {
        $this->persist();
    }

    public function updatedCheckIn(): void
    {
        $this->persist();
    }

    public function updatedCheckOut(): void
    {
        $this->persist();
    }

    public function applyCoupon(ApplyCouponAction $action): void
    {
        $totals = $this->computeTotals();

        try {
            $result = $action->preview($this->coupon_code, $totals['room_subtotal'] + $totals['extras']);
            $this->discount_amount = $result['discount'];
            session()->flash('coupon_ok', 'Coupon applied.');
        } catch (\Throwable $e) {
            $this->discount_amount = 0;
            $this->addError('coupon_code', $e->getMessage());
        }

        $this->persist();
    }

    public function confirm(CreateReservationAction $create, ApplyCouponAction $coupons): void
    {
        if (! Auth::guard('guest')->check()) {
            $this->redirect(route('guest.login'), navigate: true);

            return;
        }

        $this->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1',
            'children' => 'required|integer|min:0',
            'cart' => 'required|array|min:1',
            'special_requests' => 'nullable|string|max:2000',
        ]);

        $rooms = collect($this->cart)->map(fn ($item) => [
            'room_type_id' => (int) $item['room_type_id'],
            'quantity' => (int) $item['quantity'],
        ])->all();

        $extras = [];
        foreach ($this->extraQty as $id => $qty) {
            if ((int) $qty > 0) {
                $extras[] = ['extra_service_id' => (int) $id, 'quantity' => (int) $qty];
            }
        }

        $discount = $this->discount_amount;
        if ($this->coupon_code !== '') {
            $preview = $this->computeTotals();
            $result = $coupons->preview($this->coupon_code, $preview['room_subtotal'] + $preview['extras']);
            $discount = $result['discount'];
        }

        try {
            $result = $create->execute(
                Auth::guard('guest')->user(),
                $this->check_in,
                $this->check_out,
                $rooms,
                $this->adults,
                $this->children,
                $extras,
                $this->special_requests ?: null,
                $this->coupon_code ?: null,
                $discount,
            );

            if ($this->coupon_code !== '' && $discount > 0) {
                $coupons->execute(
                    $this->coupon_code,
                    $result['totals']['room_subtotal'] + $result['totals']['extras'],
                    Auth::guard('guest')->user(),
                    $result['reservations']->first(),
                );
            }

            session()->forget('portal.booking');
            session()->flash('status', __('portal.booking.pending'));
            $this->redirect(route('portal.dashboard.booking-show', $result['group_code']), navigate: true);
        } catch (\Throwable $e) {
            $this->addError('cart', $e->getMessage());
        }
    }

    protected function persist(): void
    {
        session([
            'portal.booking' => [
                'check_in' => $this->check_in,
                'check_out' => $this->check_out,
                'adults' => $this->adults,
                'children' => $this->children,
                'cart' => $this->cart,
                'extras' => $this->extraQty,
                'coupon_code' => $this->coupon_code,
                'discount_amount' => $this->discount_amount,
                'special_requests' => $this->special_requests,
            ],
        ]);
    }

    protected function computeTotals(): array
    {
        $nights = max(1, Carbon::parse($this->check_in)->diffInDays(Carbon::parse($this->check_out)));
        $roomSubtotal = 0.0;
        foreach ($this->cart as $item) {
            $roomSubtotal += (float) $item['price'] * (int) $item['quantity'] * $nights;
        }

        $extrasTotal = 0.0;
        $services = ExtraService::query()->where('is_active', true)->get()->keyBy('id');
        foreach ($this->extraQty as $id => $qty) {
            if ((int) $qty > 0 && $services->has($id)) {
                $extrasTotal += $services[$id]->calculateTotal($nights, (int) $qty);
            }
        }

        $discount = min($this->discount_amount, $roomSubtotal + $extrasTotal);
        $taxRate = (float) config('portal.tax_rate', 10) / 100;
        $taxable = max(0, $roomSubtotal + $extrasTotal - $discount);
        $tax = round($taxable * $taxRate, 2);

        return [
            'nights' => $nights,
            'room_subtotal' => round($roomSubtotal, 2),
            'extras' => round($extrasTotal, 2),
            'discount' => round($discount, 2),
            'tax' => $tax,
            'grand_total' => round($taxable + $tax, 2),
        ];
    }

    public function with(): array
    {
        return [
            'extras' => ExtraService::query()->where('is_active', true)->orderBy('name')->get(),
            'totals' => $this->computeTotals(),
            'currency' => app(\App\Domain\Properties\Services\PropertyContext::class)->property()?->currency ?? 'USD',
            'allRooms' => RoomType::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-4xl text-stone-900 sm:text-5xl">{{ __('portal.booking.title') }}</h1>

        <div class="mt-10 grid gap-8 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-stone-700">{{ __('portal.rooms.check_in') }}</label>
                            <input type="date" wire:model.live="check_in" class="portal-input">
                            @error('check_in') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-stone-700">{{ __('portal.rooms.check_out') }}</label>
                            <input type="date" wire:model.live="check_out" class="portal-input">
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
                    </div>
                </section>

                <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-display text-2xl text-stone-900">Rooms</h2>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach($allRooms as $type)
                                <button type="button" wire:click="addRoom({{ $type->id }})" class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-50">
                                    + {{ $type->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @forelse($cart as $index => $item)
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 pt-4" wire:key="cart-{{ $index }}">
                            <div>
                                <div class="font-semibold text-stone-900">{{ $item['name'] }}</div>
                                <div class="text-sm text-stone-500">{{ $currency }} {{ number_format($item['price'], 0) }} {{ __('portal.rooms.per_night') }}</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="number" min="1" wire:change="updateQuantity({{ $index }}, (int) $event.target.value)" class="portal-input w-20" value="{{ $item['quantity'] }}">
                                <button type="button" wire:click="removeRoom({{ $index }})" class="text-sm text-red-600">Remove</button>
                            </div>
                        </div>
                    @empty
                        <p class="mt-4 text-sm text-stone-500">Add at least one room to continue.</p>
                    @endforelse
                    @error('cart') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
                </section>

                <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="font-display text-2xl text-stone-900">{{ __('portal.booking.extras') }}</h2>
                    <div class="mt-4 space-y-3">
                        @forelse($extras as $extra)
                            <div class="flex items-center justify-between gap-3 border-t border-stone-100 pt-3">
                                <div>
                                    <div class="font-medium text-stone-900">{{ $extra->name }}</div>
                                    <div class="text-sm text-stone-500">{{ $currency }} {{ number_format($extra->price, 2) }} · {{ str_replace('_', ' ', $extra->pricing_type) }}</div>
                                </div>
                                <input type="number" min="0" wire:model.live="extraQty.{{ $extra->id }}" class="portal-input w-20" value="0">
                            </div>
                        @empty
                            <p class="text-sm text-stone-500">No extras available.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <label class="mb-1 block text-sm font-medium text-stone-700">{{ __('portal.booking.special_requests') }}</label>
                    <textarea wire:model="special_requests" rows="3" class="portal-input"></textarea>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <input type="text" wire:model="coupon_code" placeholder="Coupon code" class="portal-input max-w-xs">
                        <button type="button" wire:click="applyCoupon" class="rounded-full border border-stone-300 px-5 py-2.5 text-sm font-semibold">Apply</button>
                    </div>
                    @error('coupon_code') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                    @if(session('coupon_ok')) <p class="mt-2 text-xs text-emerald-700">{{ session('coupon_ok') }}</p> @endif
                </section>
            </div>

            <aside class="h-fit rounded-3xl border border-stone-200 bg-stone-950 p-6 text-white shadow-xl">
                <h2 class="font-display text-2xl">{{ __('portal.booking.summary') }}</h2>
                <dl class="mt-6 space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-stone-400">{{ __('portal.booking.room_price') }}</dt><dd>{{ $currency }} {{ number_format($totals['room_subtotal'], 2) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-stone-400">{{ __('portal.booking.extras') }}</dt><dd>{{ $currency }} {{ number_format($totals['extras'], 2) }}</dd></div>
                    @if($totals['discount'] > 0)
                        <div class="flex justify-between gap-3"><dt class="text-stone-400">{{ __('portal.booking.discount') }}</dt><dd>-{{ $currency }} {{ number_format($totals['discount'], 2) }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-3"><dt class="text-stone-400">{{ __('portal.booking.tax') }}</dt><dd>{{ $currency }} {{ number_format($totals['tax'], 2) }}</dd></div>
                    <div class="flex justify-between gap-3 border-t border-white/10 pt-3 text-base font-semibold"><dt>{{ __('portal.booking.grand_total') }}</dt><dd>{{ $currency }} {{ number_format($totals['grand_total'], 2) }}</dd></div>
                </dl>
                <p class="mt-3 text-xs text-stone-400">{{ $totals['nights'] }} night(s)</p>
                <button type="button" wire:click="confirm" class="portal-btn mt-8 w-full">{{ __('portal.booking.confirm') }}</button>
            </aside>
        </div>
    </div>
</div>
