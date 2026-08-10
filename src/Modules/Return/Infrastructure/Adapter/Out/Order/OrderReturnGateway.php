<?php

declare(strict_types=1);

namespace Modules\Return\Infrastructure\Adapter\Out\Order;

use Modules\Customer\Application\Port\In\ICustomerIdentityLookup;
use Modules\Order\Application\Port\In\IOrderReturnLookup;
use Modules\Return\Application\Data\ReturnableOrder;
use Modules\Return\Application\Data\ReturnableOrderItem;
use Modules\Return\Application\Port\Out\Order\IReturnOrderGateway;

final readonly class OrderReturnGateway implements IReturnOrderGateway
{
    public function __construct(
        private ICustomerIdentityLookup $customers,
        private IOrderReturnLookup $orders,
    ) {}

    public function findForIdentity(
        string $orderId,
        string $identityUserId,
    ): ?ReturnableOrder {
        $customerId = $this->customers->customerIdForIdentity($identityUserId);

        if ($customerId === null) {
            return null;
        }

        $order = $this->orders->findForCustomer($orderId, $customerId);

        if ($order === null) {
            return null;
        }

        return new ReturnableOrder(
            orderId: $order->orderId,
            customerId: $order->customerId,
            completed: $order->completed,
            currency: $order->currency,
            completedAt: $order->lastTransitionAt,
            items: array_map(
                static fn ($item): ReturnableOrderItem => new ReturnableOrderItem(
                    inventoryItemId: $item->inventoryItemId,
                    quantity: $item->quantity,
                    unitPriceAmount: $item->unitPriceAmount,
                ),
                $order->items,
            ),
        );
    }
}
