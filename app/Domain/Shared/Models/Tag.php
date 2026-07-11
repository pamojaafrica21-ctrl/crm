<?php

namespace App\Domain\Shared\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Traits\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use BelongsToProperty;

    protected $fillable = ['property_id', 'name', 'color'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function customers(): MorphToMany
    {
        return $this->morphedByMany(\App\Domain\Customers\Models\Customer::class, 'taggable');
    }
}
