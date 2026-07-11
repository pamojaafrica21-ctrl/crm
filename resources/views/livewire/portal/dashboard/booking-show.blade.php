<?php

use App\Application\Bookings\CancelReservationAction;
use App\Application\Sales\CreateInvoiceFromBookingAction;
use App\Application\Sales\ProcessPaymentAction;
use App\Domain\Customers\Models\Reservation;
use App\Domain\Shared\Enums\ReservationStatus;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public string $groupCode = '';

    public string $cancel_reason = '';

    public function mount(string $groupCode): void
    {
        $this->groupCode = $groupCode;
    }

    public function cancel(CancelReservationAction $action): void
    {
        $reservation = $this->reservations()->firstOrFail();
        abort_unless($reservation->customer_id === auth('guest')->id(), 403);

        try {
            $action->execute($reservation, $this->cancel_reason ?: null);
            session()->flash('status', 'Booking cancelled.');
        } catch (\Throwable $e) {
            $this->addError('cancel_reason', $e->getMessage());
        }
    }

    public function createInvoice(CreateInvoiceFromBookingAction $action): void
    {
        $reservation = $this->reservations()->firstOrFail();
        abort_unless($reservation->customer_id === auth('guest')->id(), 403);

        try {
            $invoice = $action->execute($this->groupCode);
            session()->flash('status', 'Invoice '.$invoice->invoice_number.' created.');
        } catch (\Throwable $e) {
            $this->addError('invoice', $e->getMessage());
        }
    }

    public function pay(ProcessPaymentAction $action): void
    {
        $reservation = $this->reservations()->with('invoice')->firstOrFail();
        abort_unless($reservation->customer_id === auth('guest')->id(), 403);

        if (! $reservation->invoice) {
            $this->addError('payment', 'Create an invoice before paying.');

            return;
        }

        try {
            $action->execute(
                $reservation->invoice,
                $reservation->invoice->outstandingBalance(),
                'manual',
                'manual',
                null,
                'Guest portal payment',
            );
            session()->flash('status', __('portal.payments.success'));
        } catch (\Throwable $e) {
            $this->addError('payment', $e->getMessage());
        }
    }

    protected function reservations()
    {
        return Reservation::query()
            ->with(['roomType', 'extraServices', 'invoice.payments'])
            ->where('customer_id', auth('guest')->id())
            ->where(function ($q) {
                $q->where('group_code', $this->groupCode)
                    ->orWhere('confirmation_number', $this->groupCode);
            });
    }

    public function with(): array
    {
        $reservations = $this->reservations()->orderBy('id')->get();
        abort_if($reservations->isEmpty(), 404);

        return [
            'reservations' => $reservations,
            'first' => $reservations->first(),
            'canCancel' => $reservations->every(fn ($r) => in_array($r->status, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)),
            'total' => $reservations->sum('total_amount'),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <a href="{{ route('portal.dashboard.bookings') }}" wire:navigate class="text-sm font-semibold text-amber-800">← Bookings</a>
        <h1 class="mt-4 font-display text-4xl text-stone-900">{{ $groupCode }}</h1>
        <p class="mt-2 text-stone-500">{{ $first->check_in->format('M j') }} – {{ $first->check_out->format('M j, Y') }}</p>

        @if(session('status'))
            <div class="mt-6 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <div class="mt-8 space-y-4">
            @foreach($reservations as $reservation)
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold text-stone-900">{{ $reservation->room_type ?: $reservation->roomType?->name }}</div>
                            <div class="text-sm text-stone-500">{{ $reservation->confirmation_number }} · {{ $reservation->adults }} adults, {{ $reservation->children }} children</div>
                        </div>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase text-amber-800">{{ __('portal.status.'.$reservation->status->value) }}</span>
                    </div>
                    @if($reservation->extraServices->isNotEmpty())
                        <ul class="mt-4 text-sm text-stone-600">
                            @foreach($reservation->extraServices as $extra)
                                <li>{{ $extra->name }} × {{ $extra->pivot->quantity }} — {{ $reservation->currency }} {{ number_format($extra->pivot->total, 2) }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="mt-4 font-semibold text-stone-900">{{ $reservation->currency }} {{ number_format($reservation->total_amount, 2) }}</div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="font-display text-2xl text-stone-900">{{ $first->currency }} {{ number_format($total, 2) }}</div>
                <div class="flex flex-wrap gap-2">
                    @if(! $first->invoice_id)
                        <button type="button" wire:click="createInvoice" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold">Create invoice</button>
                    @else
                        <a href="{{ route('portal.dashboard.invoices') }}" wire:navigate class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold">View invoices</a>
                        @if($first->invoice && $first->invoice->outstandingBalance() > 0)
                            <button type="button" wire:click="pay" class="portal-btn">{{ __('portal.payments.pay_at_hotel') }}</button>
                        @endif
                    @endif
                </div>
            </div>
            @error('invoice') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('payment') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @if($canCancel)
            <div class="mt-6 rounded-3xl border border-red-100 bg-white p-6">
                <h2 class="font-semibold text-stone-900">Cancel booking</h2>
                <textarea wire:model="cancel_reason" rows="2" class="portal-input mt-3" placeholder="Optional reason"></textarea>
                @error('cancel_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="button" wire:click="cancel" wire:confirm="Cancel this booking?" class="mt-4 rounded-full bg-red-600 px-5 py-2.5 text-sm font-semibold text-white">Cancel booking</button>
            </div>
        @endif
    </div>
</div>
