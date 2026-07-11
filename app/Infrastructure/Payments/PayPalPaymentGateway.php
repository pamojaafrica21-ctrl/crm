<?php

namespace App\Infrastructure\Payments;

use RuntimeException;

class PayPalPaymentGateway implements PaymentGatewayInterface
{
    public function charge(array $payload): array
    {
        throw new RuntimeException('PayPal payment gateway is not configured.');
    }
}
