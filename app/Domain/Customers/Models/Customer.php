<?php

namespace App\Domain\Customers\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Models\Tag;
use App\Domain\Shared\Traits\BelongsToProperty;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Customer extends Authenticatable implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authorizable;
    use BelongsToProperty;
    use CanResetPassword;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'property_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'nationality',
        'passport_number',
        'date_of_birth',
        'address',
        'company',
        'vip_level',
        'preferences',
        'emergency_contact_name',
        'emergency_contact_phone',
        'source',
        'is_active',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['password', 'remember_token']);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getNameAttribute(): string
    {
        return $this->fullName();
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

    public function loyaltyAccount(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Domain\Content\Models\LoyaltyAccount::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(\App\Domain\Content\Models\Favorite::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(\App\Domain\Content\Models\Review::class);
    }

    public function routeNotificationForMail(): ?string
    {
        return $this->email;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Infrastructure\Notifications\GuestResetPasswordNotification($token));
    }
}
