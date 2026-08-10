<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\Shipping\Domain\Enum\ShipmentStatus;
use Modules\Shipping\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ShipmentModel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Port\Out\Messaging\IMessagePublisher;
use Shared\Infrastructure\Messaging\RabbitMqMessageConsumer;

uses(DatabaseMigrations::class);

it('makes a shipment bookable after consuming payment captured', function (): void {
    $consumer = app(RabbitMqMessageConsumer::class);
    $consumer->consumeOne('shipping-payment-captured');
    shippingPaymentPurgeQueue();
    $shipment = ShipmentModel::factory()->create([
        'status' => ShipmentStatus::AwaitingPayment->value,
    ]);
    app(IMessagePublisher::class)->publish(new OutboxMessage(
        messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f90',
        eventName: 'payment.captured.v1',
        aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f91',
        payload: ['order_id' => $shipment->order_id],
        occurredAt: new DateTimeImmutable('2026-09-28T11:00:00+00:00'),
        claimToken: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f92',
        attempts: 1,
    ));

    $consumed = $consumer->consumeOne('shipping-payment-captured');

    expect($consumed)->toBeTrue();
    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->getKey(),
        'status' => ShipmentStatus::Pending->value,
    ]);
    $this->assertDatabaseHas('processed_messages', [
        'consumer' => 'shipping-payment-captured',
        'message_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f90',
    ]);
});

function shippingPaymentPurgeQueue(): void
{
    $connection = shippingPaymentConnection();
    $channel = $connection->channel();
    $queue = shippingPaymentStringConfig(
        'messaging.consumers.shipping-payment-captured.queue',
    );
    $channel->queue_purge($queue);
    $channel->queue_purge($queue.'.dead');
    $channel->close();
    $connection->close();
}

function shippingPaymentConnection(): AMQPStreamConnection
{
    return new AMQPStreamConnection(
        shippingPaymentStringConfig('messaging.rabbitmq.host'),
        shippingPaymentIntConfig('messaging.rabbitmq.port'),
        shippingPaymentStringConfig('messaging.rabbitmq.user'),
        shippingPaymentStringConfig('messaging.rabbitmq.password'),
        shippingPaymentStringConfig('messaging.rabbitmq.virtual_host'),
    );
}

function shippingPaymentStringConfig(string $key): string
{
    $value = config($key);

    if (! is_string($value)) {
        throw new RuntimeException(sprintf('Configuration "%s" must be a string.', $key));
    }

    return $value;
}

function shippingPaymentIntConfig(string $key): int
{
    $value = config($key);

    if (! is_int($value)) {
        throw new RuntimeException(sprintf('Configuration "%s" must be an integer.', $key));
    }

    return $value;
}
