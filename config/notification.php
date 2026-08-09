<?php

declare(strict_types=1);

return [
    'delivery' => [
        'batch_size' => (int) env('NOTIFICATION_BATCH_SIZE', 100),
        'claim_timeout_seconds' => (int) env('NOTIFICATION_CLAIM_TIMEOUT_SECONDS', 60),
        'maximum_attempts' => (int) env('NOTIFICATION_MAXIMUM_ATTEMPTS', 5),
        'initial_retry_delay_seconds' => (int) env('NOTIFICATION_INITIAL_RETRY_DELAY_SECONDS', 30),
        'maximum_retry_delay_seconds' => (int) env('NOTIFICATION_MAXIMUM_RETRY_DELAY_SECONDS', 3600),
    ],
];
