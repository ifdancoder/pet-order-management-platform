<?php

declare(strict_types=1);

use DateTimeImmutable;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Port\Out\Messaging\IMessagePublisher;

it('publishes a persistent versioned envelope to a bound RabbitMQ queue', function (): void {
    $connection = rabbitMqTestConnection();
    $channel = $connection->channel();
    $exchange = rabbitMqTestConfigString('messaging.rabbitmq.exchange');
    $channel->exchange_declare(
        $exchange,
        AMQPExchangeType::TOPIC,
        false,
        true,
        false,
    );
    [$queue] = $channel->queue_declare('', false, false, true, true);
    $channel->queue_bind($queue, $exchange, 'payment.#');

    try {
        app(IMessagePublisher::class)->publish(new OutboxMessage(
            messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f50',
            eventName: 'payment.authorized.v1',
            aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f51',
            payload: ['payment_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f51'],
            occurredAt: new DateTimeImmutable('2026-09-28T05:00:00+00:00'),
            claimToken: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f52',
            attempts: 1,
            correlationId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f60',
        ));

        $message = $channel->basic_get($queue, true);

        expect($message)->not->toBeNull()
            ->and($message?->get('delivery_mode'))->toBe(2)
            ->and($message?->get('content_type'))->toBe('application/json')
            ->and($message?->get('message_id'))->toBe('018f22e2-7c2a-7a33-8c4c-4ea690ad4f50')
            ->and($message?->get('correlation_id'))->toBe('018f22e2-7c2a-7a33-8c4c-4ea690ad4f60')
            ->and(json_decode((string) $message?->getBody(), true, flags: JSON_THROW_ON_ERROR))
            ->toBe([
                'message_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f50',
                'type' => 'payment.authorized.v1',
                'aggregate_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f51',
                'occurred_at' => '2026-09-28T05:00:00+00:00',
                'data' => [
                    'payment_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f51',
                ],
            ]);
    } finally {
        $channel->close();
        $connection->close();
    }
});

it('publishes without a correlation id property when the outbox message has none', function (): void {
    $connection = rabbitMqTestConnection();
    $channel = $connection->channel();
    $exchange = rabbitMqTestConfigString('messaging.rabbitmq.exchange');
    $channel->exchange_declare(
        $exchange,
        AMQPExchangeType::TOPIC,
        false,
        true,
        false,
    );
    [$queue] = $channel->queue_declare('', false, false, true, true);
    $channel->queue_bind($queue, $exchange, 'payment.#');

    try {
        app(IMessagePublisher::class)->publish(new OutboxMessage(
            messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f53',
            eventName: 'payment.authorized.v1',
            aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f54',
            payload: ['payment_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f54'],
            occurredAt: new DateTimeImmutable('2026-09-28T05:00:00+00:00'),
            claimToken: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f55',
            attempts: 1,
        ));

        $message = $channel->basic_get($queue, true);

        expect($message)->not->toBeNull()
            ->and($message?->has('correlation_id'))->toBeFalse();
    } finally {
        $channel->close();
        $connection->close();
    }
});

function rabbitMqTestConnection(): AMQPStreamConnection
{
    return new AMQPStreamConnection(
        rabbitMqTestConfigString('messaging.rabbitmq.host'),
        rabbitMqTestConfigInt('messaging.rabbitmq.port'),
        rabbitMqTestConfigString('messaging.rabbitmq.user'),
        rabbitMqTestConfigString('messaging.rabbitmq.password'),
        rabbitMqTestConfigString('messaging.rabbitmq.virtual_host'),
    );
}

function rabbitMqTestConfigString(string $key): string
{
    $value = config($key);

    if (! is_string($value)) {
        throw new RuntimeException(sprintf('Configuration "%s" must be a string.', $key));
    }

    return $value;
}

function rabbitMqTestConfigInt(string $key): int
{
    $value = config($key);

    if (! is_int($value)) {
        throw new RuntimeException(sprintf('Configuration "%s" must be an integer.', $key));
    }

    return $value;
}
