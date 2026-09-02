<?php

namespace App\Application\Billing;

use App\Domain\Billing\Models\PlatformSetting;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Organizations\Models\Organization;
use Illuminate\Support\Facades\Config;

class StripePlanSyncService
{
    public function syncPlan(SubscriptionPlan $plan): SubscriptionPlan
    {
        $secretKey = PlatformSetting::getValue('stripe_secret_key') ?: config('cashier.secret');

        if (! $secretKey) {
            return $plan;
        }

        $stripe = new \Stripe\StripeClient($secretKey);

        if (! $plan->stripe_product_id) {
            $product = $stripe->products->create([
                'name' => $plan->name,
                'description' => $plan->description,
            ]);
            $plan->stripe_product_id = $product->id;
        }

        if (! $plan->stripe_price_id) {
            $price = $stripe->prices->create([
                'product' => $plan->stripe_product_id,
                'unit_amount' => (int) ($plan->price * 100),
                'currency' => strtolower($plan->currency),
                'recurring' => [
                    'interval' => $plan->interval === 'yearly' ? 'year' : 'month',
                ],
            ]);
            $plan->stripe_price_id = $price->id;
        }

        $plan->save();

        return $plan;
    }

    public function configureStripe(): void
    {
        $secret = PlatformSetting::getValue('stripe_secret_key');
        $public = PlatformSetting::getValue('stripe_public_key');

        if ($secret) {
            Config::set('cashier.secret', $secret);
        }

        if ($public) {
            Config::set('cashier.key', $public);
        }
    }
}
