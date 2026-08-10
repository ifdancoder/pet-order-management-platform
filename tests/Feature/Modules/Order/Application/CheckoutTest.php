<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Customer\Domain\Enum\CustomerStatus;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\CustomerModel;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\InventoryItemModel;
use Modules\Order\Application\Command\Checkout\CheckoutCommand;
use Modules\Order\Application\Data\OrderItemData;
use Modules\Order\Application\Data\ShippingDetailsData;
use Modules\Order\Application\Data\ShippingQuote;
use Modules\Order\Application\Exception\CheckoutIdempotencyConflict;
use Modules\Order\Application\Exception\CheckoutRejected;
use Modules\Order\Application\Port\Out\Checkout\IShippingCheckoutGateway;
use Modules\Order\Application\Port\Out\Persistence\IOrderRepository;
use Modules\Order\Application\Query\GetOrder\GetOrderQuery;
use Modules\Order\Domain\Entity\Order;
use Modules\Order\Domain\ValueObject\OrderId;
use Modules\Promotion\Domain\Enum\DiscountType;
use Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PromotionModel;
use Shared\Application\Bus\Command\ICommandBus;
use Shared\Application\Bus\Query\IQueryBus;

uses(LazilyRefreshDatabase::class);

it('places an order and reserves inventory atomically', function (): void {
    [$customer, $inventoryItem] = checkoutRecords();

    $result = app(ICommandBus::class)->dispatch(checkoutCommand(
        customerId: $customer->getKey(),
        inventoryItemId: $inventoryItem->getKey(),
    ));

    $this->assertDatabaseHas('orders', [
        'id' => $result->orderId,
        'customer_id' => $customer->getKey(),
        'status' => 'placed',
        'total_amount' => 2500,
    ]);
    $this->assertDatabaseHas('inventory_items', [
        'id' => $inventoryItem->getKey(),
        'reserved' => 1,
    ]);
    $this->assertDatabaseHas('order_checkouts', [
        'idempotency_key' => 'checkout-request-1',
        'order_id' => $result->orderId,
        'inventory_reservation_id' => $result->inventoryReservationId,
    ]);
});

it('returns the original result when the same request is retried', function (): void {
    [$customer, $inventoryItem] = checkoutRecords();
    $command = checkoutCommand(
        customerId: $customer->getKey(),
        inventoryItemId: $inventoryItem->getKey(),
    );
    $commandBus = app(ICommandBus::class);

    $first = $commandBus->dispatch($command);
    $second = $commandBus->dispatch($command);

    expect($second)->toEqual($first);
    $this->assertDatabaseCount('orders', 1);
    $this->assertDatabaseCount('inventory_reservations', 1);
    $this->assertDatabaseHas('inventory_items', [
        'id' => $inventoryItem->getKey(),
        'reserved' => 1,
    ]);
});

it('stores the applied promotion snapshot and discounted total', function (): void {
    [$customer, $inventoryItem] = checkoutRecords();
    PromotionModel::factory()->create([
        'code' => 'SAVE20',
        'discount_type' => DiscountType::Percentage->value,
        'discount_value' => 2_000,
        'currency' => 'USD',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    $result = app(ICommandBus::class)->dispatch(checkoutCommand(
        customerId: $customer->getKey(),
        inventoryItemId: $inventoryItem->getKey(),
        promotionCodes: ['SAVE20'],
    ));
    $order = app(IQueryBus::class)->ask(new GetOrderQuery($result->orderId));

    expect($order->subtotal()->amount())->toBe(2500)
        ->and($order->discount()->amount())->toBe(500)
        ->and($order->total()->amount())->toBe(2000)
        ->and($order->promotionCodes())->toBe(['SAVE20']);
    $this->assertDatabaseHas('orders', [
        'id' => $result->orderId,
        'discount_amount' => 500,
        'total_amount' => 2000,
    ]);
    $this->assertDatabaseHas('order_applied_promotions', [
        'order_id' => $result->orderId,
        'promotion_code' => 'SAVE20',
    ]);
});

it('adds a shipping quote and creates a shipment atomically', function (): void {
    [$customer, $inventoryItem] = checkoutRecords();

    $result = app(ICommandBus::class)->dispatch(checkoutCommand(
        customerId: $customer->getKey(),
        inventoryItemId: $inventoryItem->getKey(),
        shipping: checkoutShippingDetails(),
    ));

    $this->assertDatabaseHas('orders', [
        'id' => $result->orderId,
        'shipping_method' => 'courier',
        'shipping_cost_amount' => 650,
        'total_amount' => 3150,
    ]);
    $this->assertDatabaseHas('shipments', [
        'order_id' => $result->orderId,
        'method' => 'courier',
        'cost_amount' => 650,
        'status' => 'pending',
    ]);
});

it('rejects an invalid promotion before reserving inventory', function (): void {
    [$customer, $inventoryItem] = checkoutRecords();

    $action = fn () => app(ICommandBus::class)->dispatch(checkoutCommand(
        customerId: $customer->getKey(),
        inventoryItemId: $inventoryItem->getKey(),
        promotionCodes: ['UNKNOWN'],
    ));

    expect($action)->toThrow(
        CheckoutRejected::class,
        'One or more promotions cannot be applied.',
    );
    $this->assertDatabaseHas('inventory_items', [
        'id' => $inventoryItem->getKey(),
        'reserved' => 0,
    ]);
    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseCount('inventory_reservations', 0);
    $this->assertDatabaseCount('order_checkouts', 0);
});

it('rejects reuse of an idempotency key with another request', function (): void {
    [$customer, $inventoryItem] = checkoutRecords(onHand: 3);
    $commandBus = app(ICommandBus::class);
    $commandBus->dispatch(checkoutCommand(
        customerId: $customer->getKey(),
        inventoryItemId: $inventoryItem->getKey(),
    ));

    $action = fn () => $commandBus->dispatch(checkoutCommand(
        customerId: $customer->getKey(),
        inventoryItemId: $inventoryItem->getKey(),
        quantity: 2,
    ));

    expect($action)->toThrow(CheckoutIdempotencyConflict::class);
    $this->assertDatabaseCount('orders', 1);
    $this->assertDatabaseCount('inventory_reservations', 1);
    $this->assertDatabaseHas('inventory_items', [
        'id' => $inventoryItem->getKey(),
        'reserved' => 1,
    ]);
});

it('rejects an archived customer without creating checkout state', function (): void {
    [$customer, $inventoryItem] = checkoutRecords();
    $customer->forceFill(['status' => CustomerStatus::Archived->value])->save();

    $action = fn () => app(ICommandBus::class)->dispatch(checkoutCommand(
        customerId: $customer->getKey(),
        inventoryItemId: $inventoryItem->getKey(),
    ));

    expect($action)->toThrow(CheckoutRejected::class);
    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseCount('inventory_reservations', 0);
    $this->assertDatabaseCount('order_checkouts', 0);
});

it('rolls back the reservation when order persistence fails', function (): void {
    [$customer, $inventoryItem] = checkoutRecords();
    app()->bind(IOrderRepository::class, static fn (): IOrderRepository => new class implements IOrderRepository
    {
        public function save(Order $order): void
        {
            throw new RuntimeException('Order persistence failed.');
        }

        public function findById(OrderId $orderId): ?Order
        {
            return null;
        }

        public function findByIdForUpdate(OrderId $orderId): ?Order
        {
            return null;
        }
    });

    $action = fn () => app(ICommandBus::class)->dispatch(checkoutCommand(
        customerId: $customer->getKey(),
        inventoryItemId: $inventoryItem->getKey(),
    ));

    expect($action)->toThrow(RuntimeException::class, 'Order persistence failed.');
    $this->assertDatabaseHas('inventory_items', [
        'id' => $inventoryItem->getKey(),
        'reserved' => 0,
    ]);
    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseCount('inventory_reservations', 0);
    $this->assertDatabaseCount('order_checkouts', 0);
});

it('rolls back the order and reservation when shipment persistence fails', function (): void {
    [$customer, $inventoryItem] = checkoutRecords();
    app()->bind(IShippingCheckoutGateway::class, static fn (): IShippingCheckoutGateway => new class implements IShippingCheckoutGateway
    {
        public function quote(
            ShippingDetailsData $shipping,
            string $currency,
        ): ShippingQuote {
            return new ShippingQuote('courier', 650, $currency);
        }

        public function createShipment(
            string $orderId,
            ShippingDetailsData $shipping,
            ShippingQuote $quote,
        ): void {
            throw new RuntimeException('Shipment persistence failed.');
        }
    });

    $action = fn () => app(ICommandBus::class)->dispatch(checkoutCommand(
        customerId: $customer->getKey(),
        inventoryItemId: $inventoryItem->getKey(),
        shipping: checkoutShippingDetails(),
    ));

    expect($action)->toThrow(RuntimeException::class, 'Shipment persistence failed.');
    $this->assertDatabaseHas('inventory_items', [
        'id' => $inventoryItem->getKey(),
        'reserved' => 0,
    ]);
    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseCount('shipments', 0);
    $this->assertDatabaseCount('inventory_reservations', 0);
    $this->assertDatabaseCount('order_checkouts', 0);
});

/** @return array{CustomerModel, InventoryItemModel} */
function checkoutRecords(int $onHand = 1): array
{
    return [
        CustomerModel::factory()->create(),
        InventoryItemModel::factory()->create([
            'sku' => 'PHONE-1',
            'on_hand' => $onHand,
            'reserved' => 0,
        ]),
    ];
}

/** @param list<string> $promotionCodes */
function checkoutCommand(
    string $customerId,
    string $inventoryItemId,
    int $quantity = 1,
    array $promotionCodes = [],
    ?ShippingDetailsData $shipping = null,
): CheckoutCommand {
    return new CheckoutCommand(
        idempotencyKey: 'checkout-request-1',
        customerId: $customerId,
        currency: 'USD',
        items: [
            new OrderItemData(
                inventoryItemId: $inventoryItemId,
                sku: 'PHONE-1',
                quantity: $quantity,
                unitPriceAmount: 2500,
            ),
        ],
        promotionCodes: $promotionCodes,
        shipping: $shipping,
    );
}

function checkoutShippingDetails(): ShippingDetailsData
{
    return new ShippingDetailsData(
        method: 'courier',
        recipientName: 'Jane Doe',
        line1: '100 Main Street',
        line2: null,
        city: 'New York',
        region: 'NY',
        postalCode: '10001',
        countryCode: 'US',
        weightGrams: 1000,
    );
}
