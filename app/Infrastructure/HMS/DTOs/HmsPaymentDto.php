<?php

namespace App\Infrastructure\HMS\DTOs;

readonly class HmsPaymentDto
{
    public function __construct(
        public string $externalId,
        public string $invoiceExternalId,
        public float $amount,
        public string $currency,
        public string $paymentMethod,
        public string $paymentDate,
        public ?string $reference = null,
    ) {}
}
