<?php

namespace App\Domain\Customers\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Models\Tag;
use App\Domain\Shared\Traits\BelongsToProperty;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Customer extends Model
{
    use BelongsToProperty, LogsActivity, SoftDeletes;

    protected $fillable = [
        'property_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'nationality',
        'passport_number',
        'date_of_birth',
        'address',
        'company',
        'vip_level',
        'preferences',
        'source',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(CustomerCommunication::class);
    }

    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(CustomerSegment::class, 'customer_segment_members');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function fnbOrders(): HasMany
    {
        return $this->hasMany(FnbOrder::class);
    }

    public function eventBookings(): HasMany
    {
        return $this->hasMany(EventBooking::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(\App\Domain\Sales\Models\Quote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\App\Domain\Sales\Models\Invoice::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(\App\Domain\Appointments\Models\Appointment::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(\App\Domain\Tasks\Models\Task::class);
    }
}
