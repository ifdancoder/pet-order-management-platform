<?php

declare(strict_types=1);

return [
    'outbox' => [
        'batch_size' => (int) env('OUTBOX_BATCH_SIZE', 100),
        'claim_timeout_seconds' => (int) env('OUTBOX_CLAIM_TIMEOUT_SECONDS', 60),
        'initial_retry_delay_seconds' => (int) env('OUTBOX_INITIAL_RETRY_DELAY_SECONDS', 5),
        'maximum_retry_delay_seconds' => (int) env('OUTBOX_MAXIMUM_RETRY_DELAY_SECONDS', 300),
    ],
    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', '127.0.0.1'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'virtual_host' => env('RABBITMQ_VHOST', '/'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'orderflow.events'),
        'connection_timeout_seconds' => (float) env('RABBITMQ_CONNECTION_TIMEOUT_SECONDS', 3),
        'read_write_timeout_seconds' => (float) env('RABBITMQ_READ_WRITE_TIMEOUT_SECONDS', 6),
        'heartbeat_seconds' => (int) env('RABBITMQ_HEARTBEAT_SECONDS', 3),
        'confirm_timeout_seconds' => (float) env('RABBITMQ_CONFIRM_TIMEOUT_SECONDS', 5),
    ],
    'consumers' => [
        'order-payment-status' => [
            'queue' => env('RABBITMQ_ORDER_QUEUE', 'orderflow.order.payment-status'),
            'bindings' => [
                'payment.captured.v1',
                'payment.failed.v1',
            ],
            'dead_letter_exchange' => env('RABBITMQ_DEAD_LETTER_EXCHANGE', 'orderflow.dead'),
            'prefetch_count' => (int) env('RABBITMQ_CONSUMER_PREFETCH_COUNT', 10),
        ],
        'notification-user-registered' => [
            'queue' => env('RABBITMQ_NOTIFICATION_QUEUE', 'orderflow.notification.user-registered'),
            'bindings' => [
                'user.registered.v1',
            ],
            'dead_letter_exchange' => env('RABBITMQ_DEAD_LETTER_EXCHANGE', 'orderflow.dead'),
            'prefetch_count' => (int) env('RABBITMQ_CONSUMER_PREFETCH_COUNT', 10),
        ],
        'shipping-payment-captured' => [
            'queue' => env('RABBITMQ_SHIPPING_QUEUE', 'orderflow.shipping.payment-captured'),
            'bindings' => [
                'payment.captured.v1',
            ],
            'dead_letter_exchange' => env('RABBITMQ_DEAD_LETTER_EXCHANGE', 'orderflow.dead'),
            'prefetch_count' => (int) env('RABBITMQ_CONSUMER_PREFETCH_COUNT', 10),
        ],
    ],
];
