<?php

namespace App\Infrastructure\Payments;

use RuntimeException;

class StripePaymentGateway implements PaymentGatewayInterface
{
    public function charge(array $payload): array
    {
        throw new RuntimeException('Stripe payment gateway is not configured.');
    }
}
