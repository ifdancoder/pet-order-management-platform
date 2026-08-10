<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderModel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Port\Out\Messaging\IMessagePublisher;
use Shared\Infrastructure\Messaging\RabbitMqMessageConsumer;

uses(DatabaseMigrations::class);

it('logs a consumed event and a duplicate event for the same message', function (): void {
    $logger = new RabbitMqMessageConsumerTestLogger;
    app()->forgetInstance(RabbitMqMessageConsumer::class);
    app()->instance(LoggerInterface::class, $logger);
    $consumer = app(RabbitMqMessageConsumer::class);
    $consumer->consumeOne('order-payment-status');
    rabbitMqConsumerTestPurgeQueue();
    $logger->records = [];
    $order = OrderModel::factory()->create([
        'status' => OrderStatus::Placed->value,
    ]);
    $message = new OutboxMessage(
        messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f8d',
        eventName: 'payment.captured.v1',
        aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f8e',
        payload: ['order_id' => $order->getKey()],
        occurredAt: new DateTimeImmutable('2026-09-28T05:00:00+00:00'),
        claimToken: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f8f',
        attempts: 1,
    );
    $publisher = app(IMessagePublisher::class);

    $publisher->publish($message);
    $consumer->consumeOne('order-payment-status');
    $publisher->publish($message);
    $consumer->consumeOne('order-payment-status');

    expect($logger->records)->toHaveCount(2)
        ->and($logger->records[0]['message'])->toBe('Integration message consumed.')
        ->and($logger->records[0]['context'])->toBe([
            'message_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f8d',
            'message_type' => 'payment.captured.v1',
            'consumer' => 'order-payment-status',
        ])
        ->and($logger->records[1]['message'])->toBe('Integration message duplicate, skipped.')
        ->and($logger->records[1]['context'])->toBe([
            'message_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f8d',
            'message_type' => 'payment.captured.v1',
            'consumer' => 'order-payment-status',
        ]);
});

final class RabbitMqMessageConsumerTestLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string, context: array<string, mixed>}> */
    public array $records = [];

    /** @param array<string, mixed> $context */
    public function log(
        mixed $level,
        Stringable|string $message,
        array $context = [],
    ): void {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}

it('restores the correlation id carried on the message before handling', function (): void {
    $consumer = app(RabbitMqMessageConsumer::class);
    $consumer->consumeOne('order-payment-status');
    rabbitMqConsumerTestPurgeQueue();
    $order = OrderModel::factory()->create([
        'status' => OrderStatus::Placed->value,
    ]);

    app(IMessagePublisher::class)->publish(new OutboxMessage(
        messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f86',
        eventName: 'payment.captured.v1',
        aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f87',
        payload: ['order_id' => $order->getKey()],
        occurredAt: new DateTimeImmutable('2026-09-28T05:00:00+00:00'),
        claimToken: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f88',
        attempts: 1,
        correlationId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f89',
    ));

    expect($consumer->consumeOne('order-payment-status'))->toBeTrue();

    $requestId = Context::get('request_id');

    expect(Context::get('correlation_id'))->toBe('018f22e2-7c2a-7a33-8c4c-4ea690ad4f89')
        ->and(Str::isUuid($requestId))->toBeTrue()
        ->and($requestId)->not->toBe('018f22e2-7c2a-7a33-8c4c-4ea690ad4f89');
});

it('defaults the correlation id to a generated request id for legacy messages', function (): void {
    $consumer = app(RabbitMqMessageConsumer::class);
    $consumer->consumeOne('order-payment-status');
    rabbitMqConsumerTestPurgeQueue();
    $order = OrderModel::factory()->create([
        'status' => OrderStatus::Placed->value,
    ]);

    app(IMessagePublisher::class)->publish(new OutboxMessage(
        messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f8a',
        eventName: 'payment.captured.v1',
        aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f8b',
        payload: ['order_id' => $order->getKey()],
        occurredAt: new DateTimeImmutable('2026-09-28T05:00:00+00:00'),
        claimToken: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f8c',
        attempts: 1,
    ));

    expect($consumer->consumeOne('order-payment-status'))->toBeTrue();

    $requestId = Context::get('request_id');

    expect(Str::isUuid($requestId))->toBeTrue()
        ->and(Context::get('correlation_id'))->toBe($requestId);
});

it('acknowledges duplicate payment events after applying the order transition once', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql');
    $consumer = app(RabbitMqMessageConsumer::class);
    $consumer->consumeOne('order-payment-status');
    rabbitMqConsumerTestPurgeQueue();
    $order = OrderModel::factory()->create([
        'status' => OrderStatus::Placed->value,
    ]);
    $message = new OutboxMessage(
        messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f80',
        eventName: 'payment.captured.v1',
        aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f81',
        payload: ['order_id' => $order->getKey()],
        occurredAt: new DateTimeImmutable('2026-09-28T05:00:00+00:00'),
        claimToken: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f82',
        attempts: 1,
    );
    $publisher = app(IMessagePublisher::class);

    $publisher->publish($message);
    expect($consumer->consumeOne('order-payment-status'))->toBeTrue();
    $publisher->publish($message);
    expect($consumer->consumeOne('order-payment-status'))->toBeTrue();

    $this->assertDatabaseHas('orders', [
        'id' => $order->getKey(),
        'status' => OrderStatus::Confirmed->value,
    ]);
    $this->assertDatabaseCount('processed_messages', 1);
});

it('dead letters a message when its business transition fails', function (): void {
    $consumer = app(RabbitMqMessageConsumer::class);
    $consumer->consumeOne('order-payment-status');
    rabbitMqConsumerTestPurgeQueue();
    $order = OrderModel::factory()->create([
        'status' => OrderStatus::Draft->value,
    ]);

    app(IMessagePublisher::class)->publish(new OutboxMessage(
        messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f83',
        eventName: 'payment.captured.v1',
        aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f84',
        payload: ['order_id' => $order->getKey()],
        occurredAt: new DateTimeImmutable('2026-09-28T05:00:00+00:00'),
        claimToken: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f85',
        attempts: 1,
    ));

    expect($consumer->consumeOne('order-payment-status'))->toBeTrue()
        ->and(rabbitMqConsumerTestDeadLetterId())
        ->toBe('018f22e2-7c2a-7a33-8c4c-4ea690ad4f83');
    $this->assertDatabaseCount('processed_messages', 0);
    $this->assertDatabaseHas('orders', [
        'id' => $order->getKey(),
        'status' => OrderStatus::Draft->value,
    ]);
});

function rabbitMqConsumerTestPurgeQueue(): void
{
    $connection = rabbitMqConsumerTestConnection();
    $channel = $connection->channel();
    $queue = rabbitMqConsumerTestStringConfig(
        'messaging.consumers.order-payment-status.queue',
    );
    $channel->queue_purge($queue);
    $channel->queue_purge($queue.'.dead');
    $channel->close();
    $connection->close();
}

function rabbitMqConsumerTestDeadLetterId(): ?string
{
    $connection = rabbitMqConsumerTestConnection();
    $channel = $connection->channel();
    $queue = rabbitMqConsumerTestStringConfig(
        'messaging.consumers.order-payment-status.queue',
    );
    $message = $channel->basic_get($queue.'.dead', true);
    $channel->close();
    $connection->close();

    if ($message === null || ! $message->has('message_id')) {
        return null;
    }

    $messageId = $message->get('message_id');

    return is_string($messageId) ? $messageId : null;
}

function rabbitMqConsumerTestConnection(): AMQPStreamConnection
{
    return new AMQPStreamConnection(
        rabbitMqConsumerTestStringConfig('messaging.rabbitmq.host'),
        rabbitMqConsumerTestIntConfig('messaging.rabbitmq.port'),
        rabbitMqConsumerTestStringConfig('messaging.rabbitmq.user'),
        rabbitMqConsumerTestStringConfig('messaging.rabbitmq.password'),
        rabbitMqConsumerTestStringConfig('messaging.rabbitmq.virtual_host'),
    );
}

function rabbitMqConsumerTestStringConfig(string $key): string
{
    $value = config($key);

    if (! is_string($value)) {
        throw new RuntimeException(sprintf('Configuration "%s" must be a string.', $key));
    }

    return $value;
}

function rabbitMqConsumerTestIntConfig(string $key): int
{
    $value = config($key);

    if (! is_int($value)) {
        throw new RuntimeException(sprintf('Configuration "%s" must be an integer.', $key));
    }

    return $value;
}
