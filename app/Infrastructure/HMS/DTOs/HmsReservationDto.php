<?php

namespace App\Infrastructure\HMS\DTOs;

readonly class HmsReservationDto
{
    public function __construct(
        public string $externalId,
        public string $guestExternalId,
        public string $confirmationNumber,
        public string $roomType,
        public ?string $roomNumber,
        public string $checkIn,
        public string $checkOut,
        public string $status,
        public float $totalAmount,
        public string $currency,
        public int $guests = 1,
        public ?string $specialRequests = null,
    ) {}
}
