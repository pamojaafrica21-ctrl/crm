<?php

namespace App\Domain\Customers\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Traits\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnbOrder extends Model
{
    use BelongsToProperty;

    protected $fillable = [
        'property_id',
        'customer_id',
        'order_number',
        'outlet',
        'total_amount',
        'currency',
        'status',
        'ordered_at',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'ordered_at' => 'datetime',
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
