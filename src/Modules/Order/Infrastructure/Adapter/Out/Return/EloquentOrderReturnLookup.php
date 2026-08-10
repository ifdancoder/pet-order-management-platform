<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Return;

use Modules\Order\Application\Data\ReturnableOrder;
use Modules\Order\Application\Data\ReturnableOrderItem;
use Modules\Order\Application\Port\In\IOrderReturnLookup;
use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderModel;

final readonly class EloquentOrderReturnLookup implements IOrderReturnLookup
{
    public function findForCustomer(
        string $orderId,
        string $customerId,
    ): ?ReturnableOrder {
        $order = OrderModel::query()
            ->with('items')
            ->whereKey($orderId)
            ->where('customer_id', $customerId)
            ->first();

        if ($order === null) {
            return null;
        }

        return new ReturnableOrder(
            orderId: $order->id,
            customerId: $order->customer_id,
            completed: $order->status === OrderStatus::Completed->value,
            currency: $order->currency,
            lastTransitionAt: $order->updated_at->toDateTimeImmutable(),
            items: array_values($order->items->map(
                static fn ($item): ReturnableOrderItem => new ReturnableOrderItem(
                    inventoryItemId: $item->inventory_item_id,
                    quantity: $item->quantity,
                    unitPriceAmount: $item->unit_price_amount,
                ),
            )->all()),
        );
    }
}
