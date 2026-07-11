<?php

namespace App\Domain\Appointments\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Traits\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentType extends Model
{
    use BelongsToProperty;

    protected $fillable = [
        'property_id',
        'name',
        'color',
        'default_duration_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
