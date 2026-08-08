<?php

declare(strict_types=1);

return [
    'fake' => [
        'decline' => (bool) env('PAYMENT_FAKE_DECLINE', false),
    ],
    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY', ''),
        'base_url' => env('STRIPE_BASE_URL', 'https://api.stripe.com'),
    ],
    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID', ''),
        'client_secret' => env('PAYPAL_CLIENT_SECRET', ''),
        'base_url' => env('PAYPAL_BASE_URL', 'https://api-m.sandbox.paypal.com'),
    ],
];
