<?php

namespace App\Domain\Rooms\Services;

use App\Domain\Customers\Models\Reservation;
use App\Domain\Rooms\Models\Room;
use App\Domain\Rooms\Models\RoomType;
use App\Domain\Shared\Enums\ReservationStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RoomAvailabilityService
{
    public function availableCount(RoomType $roomType, Carbon|string $checkIn, Carbon|string $checkOut): int
    {
        $checkIn = Carbon::parse($checkIn)->startOfDay();
        $checkOut = Carbon::parse($checkOut)->startOfDay();

        if ($checkOut->lte($checkIn)) {
            return 0;
        }

        $inventory = Room::query()
            ->where('room_type_id', $roomType->id)
            ->where('is_active', true)
            ->count();

        $booked = Reservation::query()
            ->where('room_type_id', $roomType->id)
            ->where('status', '!=', ReservationStatus::Cancelled->value)
            ->where('check_in', '<', $checkOut->toDateString())
            ->where('check_out', '>', $checkIn->toDateString())
            ->count();

        return max(0, $inventory - $booked);
    }

    public function isAvailable(RoomType $roomType, Carbon|string $checkIn, Carbon|string $checkOut, int $quantity = 1): bool
    {
        return $this->availableCount($roomType, $checkIn, $checkOut) >= $quantity;
    }

    public function search(
        Carbon|string $checkIn,
        Carbon|string $checkOut,
        int $adults = 1,
        int $children = 0,
        ?int $roomTypeId = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
    ): Collection {
        $query = RoomType::query()
            ->with(['amenities', 'media'])
            ->where('is_active', true)
            ->where('capacity_adults', '>=', $adults)
            ->where('capacity_children', '>=', $children)
            ->orderBy('sort_order')
            ->orderBy('base_price');

        if ($roomTypeId) {
            $query->where('id', $roomTypeId);
        }

        if ($minPrice !== null) {
            $query->where('base_price', '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $query->where('base_price', '<=', $maxPrice);
        }

        return $query->get()->map(function (RoomType $type) use ($checkIn, $checkOut) {
            $available = $this->availableCount($type, $checkIn, $checkOut);
            $type->setAttribute('available_count', $available);
            $type->setAttribute('is_available', $available > 0);

            return $type;
        })->filter(fn (RoomType $type) => $type->is_available)->values();
    }

    public function calendarOccupancy(RoomType $roomType, Carbon|string $from, Carbon|string $to): array
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->startOfDay();
        $inventory = Room::query()->where('room_type_id', $roomType->id)->where('is_active', true)->count();

        $days = [];
        for ($day = $from->copy(); $day->lt($to); $day->addDay()) {
            $next = $day->copy()->addDay();
            $available = $this->availableCount($roomType, $day, $next);
            $days[$day->toDateString()] = [
                'available' => $available,
                'sold_out' => $available <= 0,
                'inventory' => $inventory,
            ];
        }

        return $days;
    }
}
