<?php

namespace App\Domain\Content\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Traits\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use BelongsToProperty;

    protected $fillable = [
        'property_id',
        'code',
        'name',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_redemptions',
        'redemption_count',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function isValidForAmount(float $amount): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->max_redemptions !== null && $this->redemption_count >= $this->max_redemptions) {
            return false;
        }

        if ($this->min_order_amount !== null && $amount < (float) $this->min_order_amount) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        $discount = match ($this->discount_type) {
            'fixed' => (float) $this->discount_value,
            default => $amount * ((float) $this->discount_value / 100),
        };

        return round(min($discount, $amount), 2);
    }
}
