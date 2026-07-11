<?php

namespace App\Infrastructure\Payments;

use InvalidArgumentException;

class PaymentGatewayManager
{
    public function driver(?string $name = null): PaymentGatewayInterface
    {
        $name ??= (string) config('payments.driver', 'manual');
        $drivers = config('payments.drivers', []);

        if (! isset($drivers[$name]['class'])) {
            $name = (string) config('payments.driver', 'manual');
        }

        if (! isset($drivers[$name]['class'])) {
            throw new InvalidArgumentException("Payment driver [{$name}] is not defined.");
        }

        $class = $drivers[$name]['class'];

        return app($class);
    }
}
