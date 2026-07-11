<?php

namespace App\Domain\Content\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Traits\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyAccount extends Model
{
    use BelongsToProperty;

    protected $fillable = [
        'property_id',
        'customer_id',
        'points',
        'tier',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
