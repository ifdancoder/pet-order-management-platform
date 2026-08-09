<?php

declare(strict_types=1);

return [
    'domestic_country_code' => env('SHIPPING_DOMESTIC_COUNTRY_CODE', 'US'),
    'provider' => [
        'driver' => env('SHIPPING_PROVIDER_DRIVER', 'fake'),
        'base_url' => env('SHIPPING_PROVIDER_BASE_URL', 'https://shipping.example.test'),
        'api_token' => env('SHIPPING_PROVIDER_API_TOKEN', ''),
        'connect_timeout_seconds' => (int) env('SHIPPING_PROVIDER_CONNECT_TIMEOUT_SECONDS', 3),
        'timeout_seconds' => (int) env('SHIPPING_PROVIDER_TIMEOUT_SECONDS', 10),
    ],
    'dispatch' => [
        'batch_size' => (int) env('SHIPPING_BATCH_SIZE', 100),
        'claim_timeout_seconds' => (int) env('SHIPPING_CLAIM_TIMEOUT_SECONDS', 60),
        'maximum_attempts' => (int) env('SHIPPING_MAXIMUM_ATTEMPTS', 5),
        'initial_retry_delay_seconds' => (int) env('SHIPPING_INITIAL_RETRY_DELAY_SECONDS', 30),
        'maximum_retry_delay_seconds' => (int) env('SHIPPING_MAXIMUM_RETRY_DELAY_SECONDS', 3600),
    ],
];
