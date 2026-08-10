<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Modules\Payment\Application\Refund\RefundDispatcher;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Modules\Payment\Domain\Enum\RefundStatus;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PaymentModel;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PaymentRefundModel;
use Modules\Return\Domain\Enum\ReturnStatus;
use Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReturnItemModel;
use Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReturnRequestModel;
use Shared\Application\Messaging\IntegrationMessage;
use Shared\Application\Messaging\IntegrationMessageConsumer;

uses(LazilyRefreshDatabase::class);

it('coordinates an idempotent refund without network calls inside message transactions', function (): void {
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
    $payment = PaymentModel::factory()->create([
        'order_id' => $return->order_id,
        'amount' => 2500,
        'currency' => 'USD',
        'provider' => PaymentProvider::Fake->value,
        'status' => PaymentStatus::Captured->value,
        'provider_payment_id' => 'fake_payment_1',
    ]);
    $consumer = app(IntegrationMessageConsumer::class);
    $receivedMessage = fn (string $messageId): IntegrationMessage => new IntegrationMessage(
        messageId: $messageId,
        name: 'return.received.v1',
        aggregateId: $return->getKey(),
        occurredAt: new DateTimeImmutable,
        data: [
            'return_id' => $return->getKey(),
            'order_id' => $return->order_id,
            'amount' => 1000,
            'currency' => 'USD',
        ],
    );

    $consumer->consume('payment-return-received', $receivedMessage(Str::uuid7()->toString()));
    $consumer->consume('payment-return-received', $receivedMessage(Str::uuid7()->toString()));

    $this->assertDatabaseCount('payment_refunds', 1);
    $this->assertDatabaseHas('payment_refunds', [
        'return_id' => $return->getKey(),
        'payment_id' => $payment->getKey(),
        'status' => RefundStatus::Pending->value,
    ]);

    $result = app(RefundDispatcher::class)->dispatchPending();

    expect($result->completed)->toBe(1);
    $refund = PaymentRefundModel::query()
        ->where('return_id', $return->getKey())
        ->firstOrFail();
    $this->assertDatabaseHas('payment_refunds', [
        'return_id' => $return->getKey(),
        'status' => RefundStatus::Completed->value,
        'provider_refund_id' => 'fake_payment_1',
    ]);
    $this->assertDatabaseHas('outbox_messages', [
        'aggregate_id' => $refund->getKey(),
        'event_name' => 'payment.refunded.v1',
    ]);

    $consumer->consume('return-payment-refunded', new IntegrationMessage(
        messageId: Str::uuid7()->toString(),
        name: 'payment.refunded.v1',
        aggregateId: $refund->getKey(),
        occurredAt: new DateTimeImmutable,
        data: [
            'refund_id' => $refund->getKey(),
            'return_id' => $return->getKey(),
            'amount' => 1000,
            'currency' => 'USD',
        ],
    ));

    $this->assertDatabaseHas('return_requests', [
        'id' => $return->getKey(),
        'status' => ReturnStatus::Refunded->value,
    ]);
});
