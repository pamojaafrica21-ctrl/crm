<?php

namespace App\Application\Content;

use App\Domain\Content\Models\Coupon;
use App\Domain\Content\Models\CouponRedemption;
use App\Domain\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ApplyCouponAction
{
    /**
     * @return array{coupon: Coupon, discount: float}
     */
    public function execute(string $code, float $amount, ?Customer $customer = null, ?Model $redeemable = null): array
    {
        $coupon = Coupon::query()
            ->whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])
            ->first();

        if (! $coupon) {
            throw new InvalidArgumentException('Invalid coupon code.');
        }

        if (! $coupon->isValidForAmount($amount)) {
            throw new InvalidArgumentException('This coupon cannot be applied to the current order.');
        }

        $discount = $coupon->calculateDiscount($amount);

        if ($customer) {
            CouponRedemption::create([
                'coupon_id' => $coupon->id,
                'customer_id' => $customer->id,
                'redeemable_type' => $redeemable ? $redeemable->getMorphClass() : null,
                'redeemable_id' => $redeemable?->getKey(),
                'discount_amount' => $discount,
            ]);

            $coupon->increment('redemption_count');
        }

        return [
            'coupon' => $coupon,
            'discount' => $discount,
        ];
    }

    public function preview(string $code, float $amount): array
    {
        $coupon = Coupon::query()
            ->whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])
            ->first();

        if (! $coupon) {
            throw new InvalidArgumentException('Invalid coupon code.');
        }

        if (! $coupon->isValidForAmount($amount)) {
            throw new InvalidArgumentException('This coupon cannot be applied to the current order.');
        }

        return [
            'coupon' => $coupon,
            'discount' => $coupon->calculateDiscount($amount),
        ];
    }
}
