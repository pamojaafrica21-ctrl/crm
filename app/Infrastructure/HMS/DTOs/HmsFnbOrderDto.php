<?php

namespace App\Infrastructure\HMS\DTOs;

readonly class HmsFnbOrderDto
{
    public function __construct(
        public string $externalId,
        public ?string $guestExternalId,
        public string $orderNumber,
        public string $outlet,
        public float $totalAmount,
        public string $currency,
        public string $status,
        public string $orderedAt,
    ) {}
}
