<?php

namespace App\Infrastructure\HMS\DTOs;

readonly class HmsGuestDto
{
    public function __construct(
        public string $externalId,
        public string $firstName,
        public string $lastName,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $nationality = null,
        public ?string $passportNumber = null,
        public ?string $vipLevel = null,
        public ?string $company = null,
    ) {}
}
