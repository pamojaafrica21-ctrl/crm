<?php

namespace App\Infrastructure\HMS\Mock;

use App\Infrastructure\HMS\Contracts\HmsAdapterInterface;
use App\Infrastructure\HMS\DTOs\HmsEventBookingDto;
use App\Infrastructure\HMS\DTOs\HmsFnbOrderDto;
use App\Infrastructure\HMS\DTOs\HmsGuestDto;
use App\Infrastructure\HMS\DTOs\HmsInvoiceDto;
use App\Infrastructure\HMS\DTOs\HmsPaymentDto;
use App\Infrastructure\HMS\DTOs\HmsReservationDto;
use App\Infrastructure\HMS\DTOs\HmsStaffDto;

class MockHmsAdapter implements HmsAdapterInterface
{
    public function fetchGuests(int $propertyId, ?\DateTimeInterface $since = null): array
    {
        $guests = [
            1 => [
                new HmsGuestDto('G001', 'John', 'Smith', 'john.smith@email.com', '+1-555-0101', 'US', 'P123456', 'Gold', 'Acme Corp'),
                new HmsGuestDto('G002', 'Maria', 'Garcia', 'maria.garcia@email.com', '+1-555-0102', 'ES', 'P789012', 'Silver', null),
                new HmsGuestDto('G003', 'James', 'Wilson', 'james.w@email.com', '+1-555-0103', 'UK', 'P345678', null, 'Wilson Events'),
                new HmsGuestDto('G004', 'Sarah', 'Chen', 'sarah.chen@email.com', '+1-555-0104', 'CN', 'P901234', 'Platinum', 'Tech Inc'),
            ],
            2 => [
                new HmsGuestDto('G101', 'Emma', 'Brown', 'emma.b@email.com', '+1-555-0201', 'US', 'P111222', null, null),
                new HmsGuestDto('G102', 'David', 'Lee', 'david.lee@email.com', '+1-555-0202', 'KR', 'P333444', 'Gold', 'Lee Consulting'),
            ],
            3 => [
                new HmsGuestDto('G201', 'Anna', 'Kowalski', 'anna.k@email.com', '+1-555-0301', 'PL', 'P555666', 'Silver', null),
                new HmsGuestDto('G202', 'Michael', 'Taylor', 'm.taylor@email.com', '+1-555-0302', 'US', 'P777888', null, 'Taylor Group'),
            ],
        ];

        return $guests[$propertyId] ?? [];
    }

    public function fetchReservations(int $propertyId, ?\DateTimeInterface $since = null): array
    {
        $prefix = match ($propertyId) {
            1 => 'G00',
            2 => 'G10',
            3 => 'G20',
            default => 'G00',
        };

        return [
            new HmsReservationDto("R{$propertyId}001", "{$prefix}1", "CONF-{$propertyId}-001", 'Deluxe King', '101', now()->subDays(5)->toDateString(), now()->addDays(2)->toDateString(), 'checked_in', 1250.00, 'USD', 2),
            new HmsReservationDto("R{$propertyId}002", "{$prefix}2", "CONF-{$propertyId}-002", 'Standard Twin', '205', now()->addDays(3)->toDateString(), now()->addDays(7)->toDateString(), 'confirmed', 890.00, 'USD', 2),
            new HmsReservationDto("R{$propertyId}003", "{$prefix}3", "CONF-{$propertyId}-003", 'Suite', '301', now()->subDays(30)->toDateString(), now()->subDays(27)->toDateString(), 'completed', 3200.00, 'USD', 4),
        ];
    }

    public function fetchFnbOrders(int $propertyId, ?\DateTimeInterface $since = null): array
    {
        $prefix = match ($propertyId) {
            1 => 'G00',
            2 => 'G10',
            3 => 'G20',
            default => 'G00',
        };

        return [
            new HmsFnbOrderDto("F{$propertyId}001", "{$prefix}1", "FNB-{$propertyId}-001", 'Main Restaurant', 85.50, 'USD', 'completed', now()->subDay()->toDateTimeString()),
            new HmsFnbOrderDto("F{$propertyId}002", "{$prefix}2", "FNB-{$propertyId}-002", 'Pool Bar', 42.00, 'USD', 'completed', now()->subDays(2)->toDateTimeString()),
        ];
    }

    public function fetchEventBookings(int $propertyId, ?\DateTimeInterface $since = null): array
    {
        $prefix = match ($propertyId) {
            1 => 'G00',
            2 => 'G10',
            3 => 'G20',
            default => 'G00',
        };

        return [
            new HmsEventBookingDto("E{$propertyId}001", "{$prefix}3", 'Corporate Retreat', 'Grand Ballroom', now()->addDays(14)->toDateTimeString(), now()->addDays(16)->toDateTimeString(), 50, 15000.00, 'USD', 'confirmed'),
            new HmsEventBookingDto("E{$propertyId}002", "{$prefix}4", 'Wedding Reception', 'Garden Terrace', now()->addDays(30)->toDateTimeString(), now()->addDays(30)->addHours(6)->toDateTimeString(), 120, 25000.00, 'USD', 'confirmed'),
        ];
    }

    public function fetchInvoices(int $propertyId, ?\DateTimeInterface $since = null): array
    {
        $prefix = match ($propertyId) {
            1 => 'G00',
            2 => 'G10',
            3 => 'G20',
            default => 'G00',
        };

        return [
            new HmsInvoiceDto("I{$propertyId}001", "{$prefix}1", "INV-{$propertyId}-001", 1250.00, 1250.00, 'USD', 'paid', now()->subDays(5)->toDateString()),
            new HmsInvoiceDto("I{$propertyId}002", "{$prefix}3", "INV-{$propertyId}-002", 3200.00, 1600.00, 'USD', 'partial', now()->subDays(30)->toDateString(), now()->subDays(15)->toDateString()),
        ];
    }

    public function fetchPayments(int $propertyId, ?\DateTimeInterface $since = null): array
    {
        return [
            new HmsPaymentDto("P{$propertyId}001", "I{$propertyId}001", 1250.00, 'USD', 'credit_card', now()->subDays(4)->toDateString(), 'TXN-001'),
            new HmsPaymentDto("P{$propertyId}002", "I{$propertyId}002", 1600.00, 'USD', 'bank_transfer', now()->subDays(20)->toDateString(), 'TXN-002'),
        ];
    }

    public function fetchStaff(int $propertyId, ?\DateTimeInterface $since = null): array
    {
        return [
            new HmsStaffDto("S{$propertyId}001", 'Front', 'Desk', "frontdesk.p{$propertyId}@hotel.com", 'Reception', '+1-555-1001'),
            new HmsStaffDto("S{$propertyId}002", 'Sales', 'Rep', "sales.p{$propertyId}@hotel.com", 'Sales', '+1-555-1002'),
        ];
    }
}
