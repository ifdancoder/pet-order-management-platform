<?php

declare(strict_types=1);

return [
    'refund' => [
        'batch_size' => (int) env('PAYMENT_REFUND_BATCH_SIZE', 50),
        'claim_timeout_seconds' => (int) env('PAYMENT_REFUND_CLAIM_TIMEOUT_SECONDS', 60),
        'maximum_attempts' => (int) env('PAYMENT_REFUND_MAXIMUM_ATTEMPTS', 5),
        'initial_retry_delay_seconds' => (int) env('PAYMENT_REFUND_INITIAL_RETRY_DELAY_SECONDS', 5),
        'maximum_retry_delay_seconds' => (int) env('PAYMENT_REFUND_MAXIMUM_RETRY_DELAY_SECONDS', 300),
    ],
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
