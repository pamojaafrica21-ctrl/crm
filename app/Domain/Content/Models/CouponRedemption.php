<?php

namespace App\Domain\Content\Models;

use App\Domain\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CouponRedemption extends Model
{
    protected $fillable = [
        'coupon_id',
        'customer_id',
        'redeemable_type',
        'redeemable_id',
        'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function redeemable(): MorphTo
    {
        return $this->morphTo();
    }
}
