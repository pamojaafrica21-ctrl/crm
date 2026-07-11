<?php

namespace App\Domain\Shared\Enums;

enum TargetMetric: string
{
    case Revenue = 'revenue';
    case Bookings = 'bookings';
    case Conversion = 'conversion';
    case Appointments = 'appointments';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Revenue => 'Revenue',
            self::Bookings => 'Bookings',
            self::Conversion => 'Conversion Rate',
            self::Appointments => 'Appointments',
            self::Custom => 'Custom',
        };
    }
}
