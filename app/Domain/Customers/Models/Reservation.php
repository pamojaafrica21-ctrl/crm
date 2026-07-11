<?php

namespace App\Domain\Customers\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Traits\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use BelongsToProperty;

    protected $fillable = [
        'property_id',
        'customer_id',
        'confirmation_number',
        'room_type',
        'room_number',
        'check_in',
        'check_out',
        'status',
        'total_amount',
        'currency',
        'guests',
        'special_requests',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
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
