<?php

namespace App\Application\Bookings;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\Reservation;
use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Rooms\Models\ExtraService;
use App\Domain\Rooms\Models\RoomType;
use App\Domain\Rooms\Services\RoomAvailabilityService;
use App\Domain\Shared\Enums\ReservationStatus;
use App\Infrastructure\Notifications\GuestNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreateReservationAction
{
    public function __construct(
        private RoomAvailabilityService $availability,
        private PropertyContext $propertyContext,
    ) {}

    /**
     * @param  array<int, array{room_type_id:int, quantity?:int}>  $rooms
     * @param  array<int, array{extra_service_id:int, quantity:int}>  $extras
     * @return array{group_code: string, reservations: \Illuminate\Support\Collection<int, Reservation>}
     */
    public function execute(
        Customer $customer,
        string $checkIn,
        string $checkOut,
        array $rooms,
        int $adults = 1,
        int $children = 0,
        array $extras = [],
        ?string $specialRequests = null,
        ?string $couponCode = null,
        float $discountAmount = 0,
    ): array {
        $checkInDate = Carbon::parse($checkIn)->startOfDay();
        $checkOutDate = Carbon::parse($checkOut)->startOfDay();
        $nights = max(1, $checkInDate->diffInDays($checkOutDate));
        $propertyId = $this->propertyContext->id();
        $currency = $this->propertyContext->property()?->currency ?? 'USD';
        $taxRate = (float) config('portal.tax_rate', 10) / 100;
        $groupCode = 'GRP-'.strtoupper(Str::random(8));

        if ($checkOutDate->lte($checkInDate)) {
            throw new InvalidArgumentException('Check-out must be after check-in.');
        }

        if ($rooms === []) {
            throw new InvalidArgumentException('At least one room is required.');
        }

        return DB::transaction(function () use (
            $customer,
            $checkInDate,
            $checkOutDate,
            $nights,
            $rooms,
            $adults,
            $children,
            $extras,
            $specialRequests,
            $couponCode,
            $discountAmount,
            $propertyId,
            $currency,
            $taxRate,
            $groupCode,
        ) {
            $created = collect();
            $roomSubtotal = 0.0;

            foreach ($rooms as $roomRequest) {
                $quantity = max(1, (int) ($roomRequest['quantity'] ?? 1));
                $roomType = RoomType::query()->where('is_active', true)->findOrFail($roomRequest['room_type_id']);

                if (! $this->availability->isAvailable($roomType, $checkInDate, $checkOutDate, $quantity)) {
                    throw new InvalidArgumentException("{$roomType->name} is not available for the selected dates.");
                }

                for ($i = 0; $i < $quantity; $i++) {
                    $line = (float) $roomType->base_price * $nights;
                    $roomSubtotal += $line;

                    $reservation = Reservation::create([
                        'property_id' => $propertyId,
                        'customer_id' => $customer->id,
                        'room_type_id' => $roomType->id,
                        'confirmation_number' => 'RES-'.strtoupper(Str::random(10)),
                        'room_type' => $roomType->name,
                        'check_in' => $checkInDate->toDateString(),
                        'check_out' => $checkOutDate->toDateString(),
                        'status' => ReservationStatus::Pending,
                        'total_amount' => 0,
                        'tax_amount' => 0,
                        'extras_amount' => 0,
                        'discount_amount' => 0,
                        'currency' => $currency,
                        'guests' => $adults + $children,
                        'adults' => $adults,
                        'children' => $children,
                        'special_requests' => $specialRequests,
                        'source' => 'portal',
                        'group_code' => $groupCode,
                        'coupon_code' => $couponCode,
                    ]);

                    $created->push($reservation);
                }
            }

            $extrasTotal = 0.0;
            $extraRows = [];

            foreach ($extras as $extraRequest) {
                $service = ExtraService::query()->where('is_active', true)->findOrFail($extraRequest['extra_service_id']);
                $qty = max(1, (int) ($extraRequest['quantity'] ?? 1));
                $total = $service->calculateTotal($nights, $qty);
                $extrasTotal += $total;
                $extraRows[] = [
                    'service' => $service,
                    'quantity' => $qty,
                    'unit_price' => (float) $service->price,
                    'total' => $total,
                ];
            }

            $discount = min($discountAmount, $roomSubtotal + $extrasTotal);
            $taxable = max(0, $roomSubtotal + $extrasTotal - $discount);
            $tax = round($taxable * $taxRate, 2);
            $grand = round($taxable + $tax, 2);

            $perReservationRoom = $created->count() > 0 ? $roomSubtotal / $created->count() : 0;
            $perReservationExtras = $created->count() > 0 ? $extrasTotal / $created->count() : 0;
            $perReservationTax = $created->count() > 0 ? $tax / $created->count() : 0;
            $perReservationDiscount = $created->count() > 0 ? $discount / $created->count() : 0;
            $perReservationTotal = $created->count() > 0 ? $grand / $created->count() : 0;

            foreach ($created as $index => $reservation) {
                $reservation->update([
                    'total_amount' => round($perReservationTotal, 2),
                    'tax_amount' => round($perReservationTax, 2),
                    'extras_amount' => round($perReservationExtras, 2),
                    'discount_amount' => round($perReservationDiscount, 2),
                ]);

                if ($index === 0) {
                    foreach ($extraRows as $row) {
                        $reservation->extraServices()->attach($row['service']->id, [
                            'quantity' => $row['quantity'],
                            'unit_price' => $row['unit_price'],
                            'total' => $row['total'],
                        ]);
                    }
                }
            }

            GuestNotification::send(
                $customer,
                'Booking received',
                "Your booking {$groupCode} is pending confirmation. Total: {$currency} ".number_format($grand, 2),
                route('portal.dashboard.bookings')
            );

            return [
                'group_code' => $groupCode,
                'reservations' => $created->fresh(['roomType', 'extraServices']),
                'totals' => [
                    'room_subtotal' => round($roomSubtotal, 2),
                    'extras' => round($extrasTotal, 2),
                    'discount' => round($discount, 2),
                    'tax' => $tax,
                    'grand_total' => $grand,
                    'nights' => $nights,
                    'currency' => $currency,
                ],
            ];
        });
    }
}
