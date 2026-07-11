<?php

namespace App\Infrastructure\HMS\DTOs;

readonly class HmsStaffDto
{
    public function __construct(
        public string $externalId,
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $department = null,
        public ?string $phone = null,
    ) {}
}
