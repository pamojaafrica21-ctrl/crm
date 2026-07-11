<?php

namespace App\Application\Restaurant;

use App\Domain\Customers\Models\Customer;
use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Restaurant\Models\RestaurantTable;
use App\Domain\Restaurant\Models\TableReservation;
use App\Infrastructure\Notifications\GuestNotification;
use Carbon\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreateTableReservationAction
{
    public function __construct(
        private PropertyContext $propertyContext,
    ) {}

    public function execute(
        Customer $customer,
        string $reservedAt,
        int $partySize,
        ?int $tableId = null,
        ?string $specialRequests = null,
    ): TableReservation {
        $when = Carbon::parse($reservedAt);
        $partySize = max(1, $partySize);

        if ($when->isPast()) {
            throw new InvalidArgumentException('Reservation time must be in the future.');
        }

        if ($tableId) {
            $table = RestaurantTable::query()->where('is_active', true)->findOrFail($tableId);
            if ($table->capacity < $partySize) {
                throw new InvalidArgumentException('Selected table cannot seat this party size.');
            }
        }

        $reservation = TableReservation::create([
            'property_id' => $this->propertyContext->id(),
            'customer_id' => $customer->id,
            'restaurant_table_id' => $tableId,
            'reserved_at' => $when,
            'party_size' => $partySize,
            'status' => 'pending',
            'special_requests' => $specialRequests,
            'confirmation_number' => 'TBL-'.strtoupper(Str::random(8)),
        ]);

        GuestNotification::send(
            $customer,
            'Table reservation received',
            "Your table reservation {$reservation->confirmation_number} for {$partySize} guests on {$when->format('M j, Y g:i A')} is pending confirmation.",
            route('portal.dashboard.reservations')
        );

        return $reservation;
    }
}
