<?php

namespace App\Infrastructure\HMS\Sync;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\EventBooking;
use App\Domain\Customers\Models\FnbOrder;
use App\Domain\Customers\Models\Reservation;
use App\Domain\Shared\Enums\SyncStatus;
use App\Domain\Shared\Models\ExternalMapping;
use App\Domain\Shared\Models\SyncLog;
use App\Infrastructure\HMS\Contracts\HmsAdapterInterface;
use App\Infrastructure\HMS\DTOs\HmsEventBookingDto;
use App\Infrastructure\HMS\DTOs\HmsFnbOrderDto;
use App\Infrastructure\HMS\DTOs\HmsGuestDto;
use App\Infrastructure\HMS\DTOs\HmsReservationDto;
use Illuminate\Support\Facades\DB;

class HmsSyncService
{
    public function __construct(
        private readonly HmsAdapterInterface $adapter,
    ) {}

    public function syncEntity(int $propertyId, string $entityType): SyncLog
    {
        $log = SyncLog::create([
            'property_id' => $propertyId,
            'entity_type' => $entityType,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = match ($entityType) {
                'guests' => $this->syncGuests($propertyId),
                'reservations' => $this->syncReservations($propertyId),
                'fnb_orders' => $this->syncFnbOrders($propertyId),
                'event_bookings' => $this->syncEventBookings($propertyId),
                default => ['processed' => 0, 'created' => 0, 'updated' => 0],
            };

            $log->update([
                'status' => 'completed',
                'records_processed' => $result['processed'],
                'records_created' => $result['created'],
                'records_updated' => $result['updated'],
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }

        return $log->fresh();
    }

    public function syncAll(int $propertyId): array
    {
        $entities = ['guests', 'reservations', 'fnb_orders', 'event_bookings'];

        return collect($entities)
            ->map(fn (string $type) => $this->syncEntity($propertyId, $type))
            ->all();
    }

    private function syncGuests(int $propertyId): array
    {
        $processed = $created = $updated = 0;

        foreach ($this->adapter->fetchGuests($propertyId) as $dto) {
            $processed++;
            $result = $this->upsertGuest($propertyId, $dto);
            $result === 'created' ? $created++ : $updated++;
        }

        return compact('processed', 'created', 'updated');
    }

    private function upsertGuest(int $propertyId, HmsGuestDto $dto): string
    {
        return DB::transaction(function () use ($propertyId, $dto) {
            $mapping = ExternalMapping::where('property_id', $propertyId)
                ->where('entity_type', 'guest')
                ->where('external_id', $dto->externalId)
                ->first();

            $data = [
                'property_id' => $propertyId,
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'nationality' => $dto->nationality,
                'passport_number' => $dto->passportNumber,
                'vip_level' => $dto->vipLevel,
                'company' => $dto->company,
                'source' => 'hms',
            ];

            if ($mapping) {
                Customer::withoutGlobalScope('property')->where('id', $mapping->crm_id)->update($data);
                $mapping->update(['last_synced_at' => now(), 'sync_status' => SyncStatus::Synced]);

                return 'updated';
            }

            $customer = Customer::withoutGlobalScope('property')->create($data);
            ExternalMapping::create([
                'property_id' => $propertyId,
                'entity_type' => 'guest',
                'crm_id' => $customer->id,
                'external_id' => $dto->externalId,
                'last_synced_at' => now(),
                'sync_status' => SyncStatus::Synced,
            ]);

            return 'created';
        });
    }

    private function syncReservations(int $propertyId): array
    {
        $processed = $created = $updated = 0;

        foreach ($this->adapter->fetchReservations($propertyId) as $dto) {
            $processed++;
            $result = $this->upsertReservation($propertyId, $dto);
            $result === 'created' ? $created++ : $updated++;
        }

        return compact('processed', 'created', 'updated');
    }

    private function upsertReservation(int $propertyId, HmsReservationDto $dto): string
    {
        $customerId = $this->resolveCustomerId($propertyId, $dto->guestExternalId);

        $mapping = ExternalMapping::where('property_id', $propertyId)
            ->where('entity_type', 'reservation')
            ->where('external_id', $dto->externalId)
            ->first();

        $data = [
            'property_id' => $propertyId,
            'customer_id' => $customerId,
            'confirmation_number' => $dto->confirmationNumber,
            'room_type' => $dto->roomType,
            'room_number' => $dto->roomNumber,
            'check_in' => $dto->checkIn,
            'check_out' => $dto->checkOut,
            'status' => $dto->status,
            'total_amount' => $dto->totalAmount,
            'currency' => $dto->currency,
            'guests' => $dto->guests,
            'special_requests' => $dto->specialRequests,
            'synced_at' => now(),
        ];

        if ($mapping) {
            Reservation::withoutGlobalScope('property')->where('id', $mapping->crm_id)->update($data);
            $mapping->update(['last_synced_at' => now(), 'sync_status' => SyncStatus::Synced]);

            return 'updated';
        }

        $reservation = Reservation::withoutGlobalScope('property')->create($data);
        ExternalMapping::create([
            'property_id' => $propertyId,
            'entity_type' => 'reservation',
            'crm_id' => $reservation->id,
            'external_id' => $dto->externalId,
            'last_synced_at' => now(),
            'sync_status' => SyncStatus::Synced,
        ]);

        return 'created';
    }

    private function syncFnbOrders(int $propertyId): array
    {
        $processed = $created = $updated = 0;

        foreach ($this->adapter->fetchFnbOrders($propertyId) as $dto) {
            $processed++;
            $result = $this->upsertFnbOrder($propertyId, $dto);
            $result === 'created' ? $created++ : $updated++;
        }

        return compact('processed', 'created', 'updated');
    }

    private function upsertFnbOrder(int $propertyId, HmsFnbOrderDto $dto): string
    {
        $customerId = $dto->guestExternalId
            ? $this->resolveCustomerId($propertyId, $dto->guestExternalId)
            : null;

        $mapping = ExternalMapping::where('property_id', $propertyId)
            ->where('entity_type', 'fnb_order')
            ->where('external_id', $dto->externalId)
            ->first();

        $data = [
            'property_id' => $propertyId,
            'customer_id' => $customerId,
            'order_number' => $dto->orderNumber,
            'outlet' => $dto->outlet,
            'total_amount' => $dto->totalAmount,
            'currency' => $dto->currency,
            'status' => $dto->status,
            'ordered_at' => $dto->orderedAt,
            'synced_at' => now(),
        ];

        if ($mapping) {
            FnbOrder::withoutGlobalScope('property')->where('id', $mapping->crm_id)->update($data);
            $mapping->update(['last_synced_at' => now(), 'sync_status' => SyncStatus::Synced]);

            return 'updated';
        }

        $order = FnbOrder::withoutGlobalScope('property')->create($data);
        ExternalMapping::create([
            'property_id' => $propertyId,
            'entity_type' => 'fnb_order',
            'crm_id' => $order->id,
            'external_id' => $dto->externalId,
            'last_synced_at' => now(),
            'sync_status' => SyncStatus::Synced,
        ]);

        return 'created';
    }

    private function syncEventBookings(int $propertyId): array
    {
        $processed = $created = $updated = 0;

        foreach ($this->adapter->fetchEventBookings($propertyId) as $dto) {
            $processed++;
            $result = $this->upsertEventBooking($propertyId, $dto);
            $result === 'created' ? $created++ : $updated++;
        }

        return compact('processed', 'created', 'updated');
    }

    private function upsertEventBooking(int $propertyId, HmsEventBookingDto $dto): string
    {
        $customerId = $dto->guestExternalId
            ? $this->resolveCustomerId($propertyId, $dto->guestExternalId)
            : null;

        $mapping = ExternalMapping::where('property_id', $propertyId)
            ->where('entity_type', 'event_booking')
            ->where('external_id', $dto->externalId)
            ->first();

        $data = [
            'property_id' => $propertyId,
            'customer_id' => $customerId,
            'event_name' => $dto->eventName,
            'venue' => $dto->venue,
            'starts_at' => $dto->startsAt,
            'ends_at' => $dto->endsAt,
            'attendees' => $dto->attendees,
            'total_amount' => $dto->totalAmount,
            'currency' => $dto->currency,
            'status' => $dto->status,
            'synced_at' => now(),
        ];

        if ($mapping) {
            EventBooking::withoutGlobalScope('property')->where('id', $mapping->crm_id)->update($data);
            $mapping->update(['last_synced_at' => now(), 'sync_status' => SyncStatus::Synced]);

            return 'updated';
        }

        $booking = EventBooking::withoutGlobalScope('property')->create($data);
        ExternalMapping::create([
            'property_id' => $propertyId,
            'entity_type' => 'event_booking',
            'crm_id' => $booking->id,
            'external_id' => $dto->externalId,
            'last_synced_at' => now(),
            'sync_status' => SyncStatus::Synced,
        ]);

        return 'created';
    }

    private function resolveCustomerId(int $propertyId, string $guestExternalId): ?int
    {
        return ExternalMapping::where('property_id', $propertyId)
            ->where('entity_type', 'guest')
            ->where('external_id', $guestExternalId)
            ->value('crm_id');
    }
}
