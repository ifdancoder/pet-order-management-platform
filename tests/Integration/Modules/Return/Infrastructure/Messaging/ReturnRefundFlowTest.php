<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\Payment\Application\Refund\RefundDispatcher;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PaymentModel;
use Modules\Return\Domain\Enum\ReturnStatus;
use Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReturnItemModel;
use Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReturnRequestModel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Outbox\OutboxPublisher;
use Shared\Application\Port\Out\Messaging\IMessagePublisher;
use Shared\Infrastructure\Messaging\RabbitMqMessageConsumer;

uses(DatabaseMigrations::class);

it('coordinates a refund through PostgreSQL outbox and RabbitMQ', function (): void {
    $consumer = app(RabbitMqMessageConsumer::class);
    $consumer->consumeOne('payment-return-received');
    $consumer->consumeOne('return-payment-refunded');
    returnRefundPurgeQueue('payment-return-received');
    returnRefundPurgeQueue('return-payment-refunded');
    $return = ReturnRequestModel::factory()->create([
        'status' => ReturnStatus::Received->value,
        'refund_amount' => 1000,
    ]);
    ReturnItemModel::query()->create([
        'return_request_id' => $return->getKey(),
        'inventory_item_id' => '0199a480-0000-7000-8000-000000000004',
        'quantity' => 1,
        'unit_price_amount' => 1000,
    ]);
    PaymentModel::factory()->create([
        'order_id' => $return->order_id,
        'amount' => 2500,
        'currency' => 'USD',
        'provider' => PaymentProvider::Fake->value,
        'status' => PaymentStatus::Captured->value,
        'provider_payment_id' => 'fake_payment_integration',
    ]);
    app(IMessagePublisher::class)->publish(new OutboxMessage(
        messageId: '0199a480-0000-7000-8000-000000000010',
        eventName: 'return.received.v1',
        aggregateId: $return->getKey(),
        payload: [
            'return_id' => $return->getKey(),
            'order_id' => $return->order_id,
            'amount' => 1000,
            'currency' => 'USD',
        ],
        occurredAt: new DateTimeImmutable('2026-09-28T11:30:00+00:00'),
        claimToken: '0199a480-0000-7000-8000-000000000011',
        attempts: 1,
    ));

    expect($consumer->consumeOne('payment-return-received'))->toBeTrue();
    expect(app(RefundDispatcher::class)->dispatchPending()->completed)->toBe(1);
    expect(app(OutboxPublisher::class)->publishPending()->published)->toBe(1);
    expect($consumer->consumeOne('return-payment-refunded'))->toBeTrue();

    $this->assertDatabaseHas('return_requests', [
        'id' => $return->getKey(),
        'status' => ReturnStatus::Refunded->value,
    ]);
});

function returnRefundPurgeQueue(string $consumerName): void
{
    $connection = returnRefundConnection();
    $channel = $connection->channel();
    $queue = returnRefundStringConfig(sprintf('messaging.consumers.%s.queue', $consumerName));
    $channel->queue_purge($queue);
    $channel->queue_purge($queue.'.dead');
    $channel->close();
    $connection->close();
}

function returnRefundConnection(): AMQPStreamConnection
{
    return new AMQPStreamConnection(
        returnRefundStringConfig('messaging.rabbitmq.host'),
        returnRefundIntConfig('messaging.rabbitmq.port'),
        returnRefundStringConfig('messaging.rabbitmq.user'),
        returnRefundStringConfig('messaging.rabbitmq.password'),
        returnRefundStringConfig('messaging.rabbitmq.virtual_host'),
    );
}

function returnRefundStringConfig(string $key): string
{
    $value = config($key);

    if (! is_string($value)) {
        throw new RuntimeException(sprintf('Configuration "%s" must be a string.', $key));
    }

    return $value;
}

function returnRefundIntConfig(string $key): int
{
    $value = config($key);

    if (! is_int($value)) {
        throw new RuntimeException(sprintf('Configuration "%s" must be an integer.', $key));
    }

    return $value;
}
