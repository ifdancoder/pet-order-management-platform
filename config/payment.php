<?php

declare(strict_types=1);

return [
    'fake' => [
        'decline' => (bool) env('PAYMENT_FAKE_DECLINE', false),
    ],
    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY', ''),
        'base_url' => env('STRIPE_BASE_URL', 'https://api.stripe.com'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
        'webhook_tolerance_seconds' => (int) env('STRIPE_WEBHOOK_TOLERANCE_SECONDS', 300),
    ],
    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID', ''),
        'client_secret' => env('PAYPAL_CLIENT_SECRET', ''),
        'base_url' => env('PAYPAL_BASE_URL', 'https://api-m.sandbox.paypal.com'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID', ''),
    ],
];
