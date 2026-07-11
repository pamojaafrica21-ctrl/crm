<?php

namespace App\Domain\Customers\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Traits\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomerSegment extends Model
{
    use BelongsToProperty;

    protected $fillable = ['property_id', 'name', 'description', 'criteria'];

    protected function casts(): array
    {
        return ['criteria' => 'array'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_segment_members');
    }
}
