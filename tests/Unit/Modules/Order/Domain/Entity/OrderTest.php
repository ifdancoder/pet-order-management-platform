<?php

declare(strict_types=1);

use Modules\Order\Domain\Entity\Order;
use Modules\Order\Domain\Entity\OrderItem;
use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Domain\Exception\EmptyOrder;
use Modules\Order\Domain\Exception\InvalidOrderStatusTransition;
use Modules\Order\Domain\Exception\InvalidPromotionDiscount;
use Modules\Order\Domain\ValueObject\CustomerId;
use Modules\Order\Domain\ValueObject\InventoryItemId;
use Modules\Order\Domain\ValueObject\OrderId;
use Modules\Order\Domain\ValueObject\Sku;
use Shared\Domain\Exception\CurrencyMismatch;
use Shared\Domain\ValueObject\Money;

function draftOrder(): Order
{
    return Order::draft(
        id: new OrderId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f40'),
        customerId: new CustomerId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f41'),
        currency: 'USD',
    );
}

function orderItem(
    string $inventoryItemId = '018f22e2-7c2a-7a33-8c4c-4ea690ad4f42',
    int $quantity = 2,
    string $currency = 'USD',
): OrderItem {
    return new OrderItem(
        inventoryItemId: new InventoryItemId($inventoryItemId),
        sku: new Sku('SKU-001'),
        quantity: $quantity,
        unitPrice: new Money(1250, $currency),
    );
}

it('calculates the total from immutable item price snapshots', function () {
    $order = draftOrder();

    $order->addItem(orderItem());
    $order->addItem(orderItem(
        inventoryItemId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f43',
        quantity: 1,
    ));

    expect($order->total()->amount())->toBe(3750)
        ->and($order->total()->currency())->toBe('USD');
});

it('moves through the fulfillment lifecycle', function () {
    $order = draftOrder();
    $order->addItem(orderItem());

    $order->place();
    expect($order->status())->toBe(OrderStatus::Placed);
    $order->confirm();
    expect($order->status())->toBe(OrderStatus::Confirmed);
    $order->startProcessing();
    expect($order->status())->toBe(OrderStatus::Processing);
    $order->markShipped();
    expect($order->status())->toBe(OrderStatus::Shipped);
    $order->complete();
    expect($order->status())->toBe(OrderStatus::Completed);
});

it('supports payment failure and retry before confirmation', function () {
    $order = draftOrder();
    $order->addItem(orderItem());
    $order->place();

    $order->markPaymentFailed();
    expect($order->status())->toBe(OrderStatus::PaymentFailed);
    $order->retryPayment();
    expect($order->status())->toBe(OrderStatus::Placed);
});

it('rejects placing an empty order', function () {
    draftOrder()->place();
})->throws(EmptyOrder::class);

it('rejects invalid lifecycle transitions', function () {
    $order = draftOrder();
    $order->addItem(orderItem());

    $order->confirm();
})->throws(InvalidOrderStatusTransition::class);

it('prevents item changes after placement', function () {
    $order = draftOrder();
    $order->addItem(orderItem());
    $order->place();

    $order->removeItem(
        new InventoryItemId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f42'),
    );
})->throws(InvalidOrderStatusTransition::class);

it('allows cancellation only before processing', function () {
    $order = draftOrder();
    $order->addItem(orderItem());
    $order->place();
    $order->confirm();
    $order->startProcessing();

    $order->cancel();
})->throws(InvalidOrderStatusTransition::class);

it('rejects item prices in another currency', function () {
    draftOrder()->addItem(orderItem(currency: 'EUR'));
})->throws(CurrencyMismatch::class);

it('applies a promotion discount to the order total', function () {
    $order = draftOrder();
    $order->addItem(orderItem());

    $order->applyPromotionDiscount(
        new Money(500, 'USD'),
        ['SAVE20'],
    );

    expect($order->subtotal()->amount())->toBe(2500)
        ->and($order->discount()->amount())->toBe(500)
        ->and($order->total()->amount())->toBe(2000)
        ->and($order->promotionCodes())->toBe(['SAVE20']);
});

it('rejects a promotion discount above the subtotal', function () {
    $order = draftOrder();
    $order->addItem(orderItem());

    $order->applyPromotionDiscount(
        new Money(2501, 'USD'),
        ['TOO-MUCH'],
    );
})->throws(InvalidPromotionDiscount::class);
