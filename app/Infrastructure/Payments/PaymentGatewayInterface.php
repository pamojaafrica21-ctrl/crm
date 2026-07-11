<?php

namespace App\Infrastructure\Payments;

interface PaymentGatewayInterface
{
    /**
     * @param  array{amount: float, currency: string, reference?: string, description?: string, metadata?: array}  $payload
     * @return array{success: bool, reference: string, message?: string, raw?: mixed}
     */
    public function charge(array $payload): array;
}
