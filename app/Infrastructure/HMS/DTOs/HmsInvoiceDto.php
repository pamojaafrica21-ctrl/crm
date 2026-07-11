<?php

namespace App\Infrastructure\HMS\DTOs;

readonly class HmsInvoiceDto
{
    public function __construct(
        public string $externalId,
        public ?string $guestExternalId,
        public string $invoiceNumber,
        public float $totalAmount,
        public float $amountPaid,
        public string $currency,
        public string $status,
        public string $issueDate,
        public ?string $dueDate = null,
    ) {}
}
