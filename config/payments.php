<?php

return [

    'driver' => env('PAYMENT_DRIVER', 'manual'),

    'drivers' => [
        'manual' => [
            'class' => App\Infrastructure\Payments\ManualPaymentGateway::class,
        ],
        'stripe' => [
            'class' => App\Infrastructure\Payments\StripePaymentGateway::class,
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
        ],
        'paypal' => [
            'class' => App\Infrastructure\Payments\PayPalPaymentGateway::class,
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
        ],
        'mpesa' => [
            'class' => App\Infrastructure\Payments\MpesaPaymentGateway::class,
            'consumer_key' => env('MPESA_CONSUMER_KEY'),
            'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
            'shortcode' => env('MPESA_SHORTCODE'),
        ],
    ],

];
