<?php

namespace App\Infrastructure\Payments;

use RuntimeException;

class MpesaPaymentGateway implements PaymentGatewayInterface
{
    public function charge(array $payload): array
    {
        throw new RuntimeException('M-Pesa payment gateway is not configured.');
    }
}
