<?php

namespace App\Domain\Customers\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Traits\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventBooking extends Model
{
    use BelongsToProperty;

    protected $fillable = [
        'property_id',
        'customer_id',
        'event_name',
        'venue',
        'starts_at',
        'ends_at',
        'attendees',
        'total_amount',
        'currency',
        'status',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'synced_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
