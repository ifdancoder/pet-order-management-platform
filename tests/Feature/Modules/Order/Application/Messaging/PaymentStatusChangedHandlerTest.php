<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Domain\Exception\InvalidOrderStatusTransition;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderModel;
use Shared\Application\Messaging\IntegrationMessage;
use Shared\Application\Messaging\IntegrationMessageConsumer;

uses(LazilyRefreshDatabase::class);

it('confirms an order once when a captured payment is delivered twice', function (): void {
    $order = OrderModel::factory()->create([
        'status' => OrderStatus::Placed->value,
    ]);
    $message = paymentStatusTestMessage(
        'payment.captured.v1',
        $order->getKey(),
    );
    $consumer = app(IntegrationMessageConsumer::class);

    expect($consumer->consume($message))->toBeTrue()
        ->and($consumer->consume($message))->toBeFalse();
    $this->assertDatabaseHas('orders', [
        'id' => $order->getKey(),
        'status' => OrderStatus::Confirmed->value,
    ]);
    $this->assertDatabaseCount('processed_messages', 1);
});

it('marks an order payment as failed', function (): void {
    $order = OrderModel::factory()->create([
        'status' => OrderStatus::Placed->value,
    ]);

    app(IntegrationMessageConsumer::class)->consume(paymentStatusTestMessage(
        'payment.failed.v1',
        $order->getKey(),
    ));

    $this->assertDatabaseHas('orders', [
        'id' => $order->getKey(),
        'status' => OrderStatus::PaymentFailed->value,
    ]);
});

it('rolls back inbox state when an order transition is rejected', function (): void {
    $order = OrderModel::factory()->create([
        'status' => OrderStatus::Draft->value,
    ]);

    expect(fn (): bool => app(IntegrationMessageConsumer::class)->consume(
        paymentStatusTestMessage('payment.captured.v1', $order->getKey()),
    ))->toThrow(InvalidOrderStatusTransition::class);

    $this->assertDatabaseCount('processed_messages', 0);
    $this->assertDatabaseHas('orders', [
        'id' => $order->getKey(),
        'status' => OrderStatus::Draft->value,
    ]);
});

function paymentStatusTestMessage(string $name, string $orderId): IntegrationMessage
{
    return new IntegrationMessage(
        messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f62',
        name: $name,
        aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f63',
        occurredAt: new DateTimeImmutable('2026-09-28T05:00:00+00:00'),
        data: ['order_id' => $orderId],
    );
}
