<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Order\Application\Command\AddOrderItem\AddOrderItemCommand;
use Modules\Order\Application\Command\CreateOrderDraft\CreateOrderDraftCommand;
use Modules\Order\Application\Command\PlaceOrder\PlaceOrderCommand;
use Modules\Order\Application\Command\RemoveOrderItem\RemoveOrderItemCommand;
use Modules\Order\Application\Command\TransitionOrder\TransitionOrderCommand;
use Modules\Order\Application\Data\OrderItemData;
use Modules\Order\Application\Enum\OrderTransition;
use Modules\Order\Application\Query\GetOrder\GetOrderQuery;
use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Domain\Exception\InvalidOrderStatusTransition;
use Shared\Application\Bus\Command\ICommandBus;
use Shared\Application\Bus\Query\IQueryBus;

uses(LazilyRefreshDatabase::class);

function orderItemData(
    string $inventoryItemId = '018f22e2-7c2a-7a33-8c4c-4ea690ad4f42',
    int $quantity = 2,
    int $unitPriceAmount = 1250,
): OrderItemData {
    return new OrderItemData(
        inventoryItemId: $inventoryItemId,
        sku: 'SKU-001',
        quantity: $quantity,
        unitPriceAmount: $unitPriceAmount,
    );
}

it('creates and rehydrates a draft with price snapshots', function () {
    $order = app(ICommandBus::class)->dispatch(new CreateOrderDraftCommand(
        customerId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f41',
        currency: 'USD',
        items: [
            orderItemData(),
            orderItemData(
                inventoryItemId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f43',
                quantity: 1,
                unitPriceAmount: 500,
            ),
        ],
    ));

    $rehydrated = app(IQueryBus::class)->ask(
        new GetOrderQuery($order->id()->value()),
    );

    expect($rehydrated->status())->toBe(OrderStatus::Draft)
        ->and($rehydrated->items())->toHaveCount(2)
        ->and($rehydrated->total()->amount())->toBe(3000);
    $this->assertDatabaseHas('orders', [
        'id' => $order->id()->value(),
        'customer_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f41',
        'currency' => 'USD',
        'status' => 'draft',
        'total_amount' => 3000,
    ]);
    $this->assertDatabaseCount('order_items', 2);
});

it('adds and removes items while the order is a draft', function () {
    $commandBus = app(ICommandBus::class);
    $order = $commandBus->dispatch(new CreateOrderDraftCommand(
        customerId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f41',
        currency: 'USD',
    ));

    $commandBus->dispatch(new AddOrderItemCommand(
        orderId: $order->id()->value(),
        item: orderItemData(),
    ));
    $updatedOrder = $commandBus->dispatch(new RemoveOrderItemCommand(
        orderId: $order->id()->value(),
        inventoryItemId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f42',
    ));

    expect($updatedOrder->items())->toBe([])
        ->and($updatedOrder->total()->amount())->toBe(0);
    $this->assertDatabaseCount('order_items', 0);
});

it('places and advances an order through fulfillment', function () {
    $commandBus = app(ICommandBus::class);
    $order = $commandBus->dispatch(new CreateOrderDraftCommand(
        customerId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f41',
        currency: 'USD',
        items: [orderItemData()],
    ));

    $order = $commandBus->dispatch(new PlaceOrderCommand($order->id()->value()));

    foreach ([
        OrderTransition::Confirm,
        OrderTransition::StartProcessing,
        OrderTransition::MarkShipped,
        OrderTransition::Complete,
    ] as $transition) {
        $order = $commandBus->dispatch(new TransitionOrderCommand(
            $order->id()->value(),
            $transition,
        ));
    }

    expect($order->status())->toBe(OrderStatus::Completed);
    $this->assertDatabaseHas('orders', [
        'id' => $order->id()->value(),
        'status' => 'completed',
        'total_amount' => 2500,
    ]);
});

it('rolls back invalid state transitions', function () {
    $commandBus = app(ICommandBus::class);
    $order = $commandBus->dispatch(new CreateOrderDraftCommand(
        customerId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f41',
        currency: 'USD',
        items: [orderItemData()],
    ));

    expect(fn () => $commandBus->dispatch(new TransitionOrderCommand(
        $order->id()->value(),
        OrderTransition::Confirm,
    )))->toThrow(InvalidOrderStatusTransition::class);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id()->value(),
        'status' => 'draft',
    ]);
});
