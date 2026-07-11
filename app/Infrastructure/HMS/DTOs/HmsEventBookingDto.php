<?php

namespace App\Infrastructure\HMS\DTOs;

readonly class HmsEventBookingDto
{
    public function __construct(
        public string $externalId,
        public ?string $guestExternalId,
        public string $eventName,
        public ?string $venue,
        public string $startsAt,
        public ?string $endsAt,
        public int $attendees,
        public float $totalAmount,
        public string $currency,
        public string $status,
    ) {}
}
