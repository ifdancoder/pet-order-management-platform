<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderModel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Port\Out\Messaging\IMessagePublisher;
use Shared\Infrastructure\Messaging\RabbitMqMessageConsumer;

uses(DatabaseMigrations::class);

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
