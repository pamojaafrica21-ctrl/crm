<?php

namespace App\Domain\Content\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Traits\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Favorite extends Model
{
    use BelongsToProperty;

    protected $fillable = [
        'property_id',
        'customer_id',
        'favoritable_type',
        'favoritable_id',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }
}
