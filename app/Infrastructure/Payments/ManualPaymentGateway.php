<?php

namespace App\Infrastructure\Payments;

use Illuminate\Support\Str;

class ManualPaymentGateway implements PaymentGatewayInterface
{
    public function charge(array $payload): array
    {
        return [
            'success' => true,
            'reference' => $payload['reference'] ?? 'MAN-'.strtoupper(Str::random(10)),
            'message' => 'Payment recorded for settlement at the property.',
        ];
    }
}
