<?php

declare(strict_types=1);

use Modules\Order\Application\Checkout\CheckoutContext;
use Modules\Order\Application\Checkout\CheckoutRulePipeline;
use Modules\Order\Application\Checkout\Rule\CustomerCanOrderRule;
use Modules\Order\Application\Checkout\Rule\InventoryAvailableRule;
use Modules\Order\Application\Checkout\Rule\OrderNotEmptyRule;
use Modules\Order\Application\Data\OrderItemData;
use Modules\Order\Application\Exception\CheckoutRejected;
use Modules\Order\Application\Port\Out\Checkout\ICustomerCheckoutGateway;
use Modules\Order\Application\Port\Out\Checkout\IInventoryCheckoutGateway;

it('accepts checkout when every rule passes', function (): void {
    $pipeline = checkoutPipeline(customerCanOrder: true, inventoryAvailable: true);

    $pipeline->check(checkoutContext());

    expect(true)->toBeTrue();
});

it('rejects a customer that cannot order', function (): void {
    $pipeline = checkoutPipeline(customerCanOrder: false, inventoryAvailable: true);

    $action = fn () => $pipeline->check(checkoutContext());

    expect($action)->toThrow(
        CheckoutRejected::class,
        'Customer is not allowed to place an order.',
    );
});

it('rejects an empty order before checking inventory', function (): void {
    $inventory = new class implements IInventoryCheckoutGateway
    {
        public function isAvailable(array $items): bool
        {
            throw new LogicException('Inventory must not be checked.');
        }

        public function reserve(string $reservationKey, array $items): string
        {
            throw new LogicException('Inventory must not be reserved.');
        }
    };
    $pipeline = new CheckoutRulePipeline([
        new OrderNotEmptyRule,
        new InventoryAvailableRule($inventory),
    ]);

    $action = fn () => $pipeline->check(new CheckoutContext(
        customerId: '018f1500-0000-7000-8000-000000000001',
        currency: 'USD',
        items: [],
    ));

    expect($action)->toThrow(
        CheckoutRejected::class,
        'An order must contain at least one item.',
    );
});

it('rejects unavailable inventory', function (): void {
    $pipeline = checkoutPipeline(customerCanOrder: true, inventoryAvailable: false);

    $action = fn () => $pipeline->check(checkoutContext());

    expect($action)->toThrow(
        CheckoutRejected::class,
        'Requested inventory is not available.',
    );
});

function checkoutPipeline(
    bool $customerCanOrder,
    bool $inventoryAvailable,
): CheckoutRulePipeline {
    $customers = new class($customerCanOrder) implements ICustomerCheckoutGateway
    {
        public function __construct(private readonly bool $allowed) {}

        public function canOrder(string $customerId): bool
        {
            return $this->allowed;
        }
    };
    $inventory = new class($inventoryAvailable) implements IInventoryCheckoutGateway
    {
        public function __construct(private readonly bool $available) {}

        public function isAvailable(array $items): bool
        {
            return $this->available;
        }

        public function reserve(string $reservationKey, array $items): string
        {
            throw new LogicException('Inventory reservation is outside this unit test.');
        }
    };

    return new CheckoutRulePipeline([
        new CustomerCanOrderRule($customers),
        new OrderNotEmptyRule,
        new InventoryAvailableRule($inventory),
    ]);
}

function checkoutContext(): CheckoutContext
{
    return new CheckoutContext(
        customerId: '018f1500-0000-7000-8000-000000000001',
        currency: 'USD',
        items: [
            new OrderItemData(
                inventoryItemId: '018f1500-0000-7000-8000-000000000002',
                sku: 'PHONE-1',
                quantity: 1,
                unitPriceAmount: 100_00,
            ),
        ],
    );
}
