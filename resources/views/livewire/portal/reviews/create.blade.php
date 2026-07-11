<?php

use App\Domain\Content\Models\Review;
use App\Domain\Customers\Models\FnbOrder;
use App\Domain\Customers\Models\Reservation;
use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Rooms\Models\RoomType;
use App\Domain\Shared\Enums\ReservationStatus;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public string $reviewable_type = '';
    public ?int $reviewable_id = null;
    public int $rating = 5;
    public string $title = '';
    public string $body = '';

    public function submit(): void
    {
        $customer = auth('guest')->user();

        $validated = $this->validate([
            'reviewable_type' => 'required|in:room,order',
            'reviewable_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'body' => 'required|string|max:5000',
        ]);

        if ($validated['reviewable_type'] === 'room') {
            $completed = Reservation::query()
                ->where('customer_id', $customer->id)
                ->where('room_type_id', $validated['reviewable_id'])
                ->whereIn('status', [ReservationStatus::CheckedOut->value, ReservationStatus::Completed->value])
                ->exists();

            abort_unless($completed, 403, __('portal.reviews.only_completed'));

            $type = RoomType::class;
            $id = $validated['reviewable_id'];
        } else {
            $order = FnbOrder::query()
                ->where('customer_id', $customer->id)
                ->whereKey($validated['reviewable_id'])
                ->whereIn('status', ['completed', 'delivered'])
                ->firstOrFail();

            $type = FnbOrder::class;
            $id = $order->id;
        }

        Review::create([
            'property_id' => app(PropertyContext::class)->id(),
            'customer_id' => $customer->id,
            'reviewable_type' => $type,
            'reviewable_id' => $id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?: null,
            'body' => $validated['body'],
            'is_approved' => false,
        ]);

        session()->flash('status', __('portal.reviews.thanks'));
        $this->reset('title', 'body', 'reviewable_id');
        $this->rating = 5;
    }

    public function with(): array
    {
        $customer = auth('guest')->user();

        return [
            'roomTypes' => RoomType::query()
                ->whereIn('id', Reservation::query()
                    ->where('customer_id', $customer->id)
                    ->whereIn('status', [ReservationStatus::CheckedOut->value, ReservationStatus::Completed->value])
                    ->whereNotNull('room_type_id')
                    ->pluck('room_type_id'))
                ->orderBy('name')
                ->get(),
            'orders' => FnbOrder::query()
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['completed', 'delivered'])
                ->latest()
                ->limit(20)
                ->get(),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-20">
    <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-4xl text-stone-900">{{ __('portal.reviews.title') }}</h1>

        @if(session('status'))
            <div class="mt-6 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <form wire:submit="submit" class="mt-8 space-y-4 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Review type</label>
                <select wire:model.live="reviewable_type" class="portal-input">
                    <option value="">Select...</option>
                    <option value="room">Room stay</option>
                    <option value="order">Dining order</option>
                </select>
            </div>

            @if($reviewable_type === 'room')
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Room</label>
                    <select wire:model="reviewable_id" class="portal-input">
                        <option value="">Select...</option>
                        @foreach($roomTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif($reviewable_type === 'order')
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Order</label>
                    <select wire:model="reviewable_id" class="portal-input">
                        <option value="">Select...</option>
                        @foreach($orders as $order)
                            <option value="{{ $order->id }}">{{ $order->order_number }} — {{ $order->ordered_at?->format('M j') }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Rating</label>
                <select wire:model="rating" class="portal-input">
                    @for($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Title</label>
                <input type="text" wire:model="title" class="portal-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Review</label>
                <textarea wire:model="body" rows="4" class="portal-input"></textarea>
                @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="portal-btn w-full">{{ __('portal.reviews.submit') }}</button>
        </form>
    </div>
</div>
