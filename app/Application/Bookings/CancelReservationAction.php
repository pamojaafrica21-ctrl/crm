<?php

namespace App\Application\Bookings;

use App\Domain\Customers\Models\Reservation;
use App\Domain\Shared\Enums\ReservationStatus;
use App\Infrastructure\Notifications\GuestNotification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelReservationAction
{
    public function execute(Reservation $reservation, ?string $reason = null): Reservation
    {
        if ($reservation->status === ReservationStatus::Cancelled) {
            throw new InvalidArgumentException('Reservation is already cancelled.');
        }

        if (in_array($reservation->status, [ReservationStatus::CheckedIn, ReservationStatus::CheckedOut, ReservationStatus::Completed], true)) {
            throw new InvalidArgumentException('This reservation can no longer be cancelled.');
        }

        return DB::transaction(function () use ($reservation, $reason) {
            $targets = $reservation->group_code
                ? Reservation::query()->where('group_code', $reservation->group_code)->get()
                : collect([$reservation]);

            foreach ($targets as $target) {
                $notes = $target->special_requests;
                if ($reason) {
                    $notes = trim(($notes ? $notes."\n" : '')."Cancellation: {$reason}");
                }

                $target->update([
                    'status' => ReservationStatus::Cancelled,
                    'special_requests' => $notes,
                ]);
            }

            $reservation->refresh();

            if ($reservation->customer) {
                GuestNotification::send(
                    $reservation->customer,
                    'Booking cancelled',
                    'Your booking '.($reservation->group_code ?: $reservation->confirmation_number).' has been cancelled.',
                    route('portal.dashboard.bookings')
                );
            }

            return $reservation;
        });
    }
}
