<?php

namespace App\Domain\Organizations\Models;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Properties\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;

class Organization extends Model
{
    use Billable;

    public const STATUS_TRIAL = 'trial';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'logo_path',
        'status',
        'trial_ends_at',
        'subscription_plan_id',
        'owner_id',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Organization $org): void {
            if (empty($org->slug)) {
                $org->slug = Str::slug($org->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null
            && $this->owner_id
            && (int) $this->owner_id === (int) $user->id;
    }

    public function isOnTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscribed('default')
            || $this->status === self::STATUS_ACTIVE;
    }

    public function onGracePeriod(): bool
    {
        return (bool) $this->subscription('default')?->onGracePeriod();
    }

    public function canAccessPlatform(): bool
    {
        if ($this->status === self::STATUS_SUSPENDED) {
            return false;
        }

        if ($this->onGracePeriod()) {
            return true;
        }

        if ($this->status === self::STATUS_CANCELLED) {
            return false;
        }

        if ($this->isOnTrial()) {
            return true;
        }

        if ($this->hasActiveSubscription()) {
            return true;
        }

        return $this->status === self::STATUS_PAST_DUE;
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }
}
