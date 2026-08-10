<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper;

use Illuminate\Support\Collection;
use Modules\Order\Domain\Entity\Order;
use Modules\Order\Domain\Entity\OrderItem;
use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Domain\ValueObject\CustomerId;
use Modules\Order\Domain\ValueObject\InventoryItemId;
use Modules\Order\Domain\ValueObject\OrderId;
use Modules\Order\Domain\ValueObject\Sku;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderItemModel;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderModel;
use Shared\Domain\ValueObject\Money;

final class OrderMapper
{
    /**
     * @param  Collection<int, OrderItemModel>  $itemModels
     * @param  list<string>  $promotionCodes
     */
    public function toDomain(
        OrderModel $orderModel,
        Collection $itemModels,
        array $promotionCodes = [],
    ): Order {
        return new Order(
            id: new OrderId($orderModel->id),
            customerId: new CustomerId($orderModel->customer_id),
            currency: $orderModel->currency,
            status: OrderStatus::from($orderModel->status),
            items: array_values($itemModels->map(
                static fn (OrderItemModel $itemModel): OrderItem => new OrderItem(
                    inventoryItemId: new InventoryItemId($itemModel->inventory_item_id),
                    sku: new Sku($itemModel->sku),
                    quantity: $itemModel->quantity,
                    unitPrice: new Money(
                        $itemModel->unit_price_amount,
                        $orderModel->currency,
                    ),
                ),
            )->all()),
            discount: new Money(
                $orderModel->discount_amount,
                $orderModel->currency,
            ),
            promotionCodes: $promotionCodes,
            shippingCost: new Money(
                $orderModel->shipping_cost_amount,
                $orderModel->currency,
            ),
            shippingMethod: $orderModel->shipping_method,
        );
    }

    public function mapToModel(Order $order, OrderModel $model): void
    {
        $model->id = $order->id()->value();
        $model->customer_id = $order->customerId()->value();
        $model->currency = $order->currency();
        $model->status = $order->status()->value;
        $model->discount_amount = $order->discount()->amount();
        $model->total_amount = $order->total()->amount();
        $model->shipping_cost_amount = $order->shippingCost()->amount();
        $model->shipping_method = $order->shippingMethod();
    }
}
