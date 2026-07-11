<?php

namespace App\Infrastructure\HMS\Contracts;

use App\Infrastructure\HMS\DTOs\HmsEventBookingDto;
use App\Infrastructure\HMS\DTOs\HmsFnbOrderDto;
use App\Infrastructure\HMS\DTOs\HmsGuestDto;
use App\Infrastructure\HMS\DTOs\HmsInvoiceDto;
use App\Infrastructure\HMS\DTOs\HmsPaymentDto;
use App\Infrastructure\HMS\DTOs\HmsReservationDto;
use App\Infrastructure\HMS\DTOs\HmsStaffDto;

interface HmsAdapterInterface
{
    public function fetchGuests(int $propertyId, ?\DateTimeInterface $since = null): array;

    public function fetchReservations(int $propertyId, ?\DateTimeInterface $since = null): array;

    public function fetchFnbOrders(int $propertyId, ?\DateTimeInterface $since = null): array;

    public function fetchEventBookings(int $propertyId, ?\DateTimeInterface $since = null): array;

    public function fetchInvoices(int $propertyId, ?\DateTimeInterface $since = null): array;

    public function fetchPayments(int $propertyId, ?\DateTimeInterface $since = null): array;

    public function fetchStaff(int $propertyId, ?\DateTimeInterface $since = null): array;
}
