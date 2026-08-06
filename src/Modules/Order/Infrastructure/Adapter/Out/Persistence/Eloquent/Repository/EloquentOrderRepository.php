<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository;

use Modules\Order\Application\Port\Out\Persistence\IOrderRepository;
use Modules\Order\Domain\Entity\Order;
use Modules\Order\Domain\ValueObject\OrderId;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\OrderMapper;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderItemModel;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderModel;

final readonly class EloquentOrderRepository implements IOrderRepository
{
    public function __construct(
        private OrderMapper $mapper,
    ) {}

    public function save(Order $order): void
    {
        $orderModel = OrderModel::query()->find($order->id()->value())
            ?? new OrderModel;
        $this->mapper->mapToModel($order, $orderModel);
        $orderModel->save();

        $inventoryItemIds = [];

        foreach ($order->items() as $item) {
            $inventoryItemId = $item->inventoryItemId()->value();
            $inventoryItemIds[] = $inventoryItemId;
            $itemModel = OrderItemModel::query()
                ->where('order_id', $order->id()->value())
                ->where('inventory_item_id', $inventoryItemId)
                ->first() ?? new OrderItemModel;

            $itemModel->order_id = $order->id()->value();
            $itemModel->inventory_item_id = $inventoryItemId;
            $itemModel->sku = $item->sku()->value();
            $itemModel->quantity = $item->quantity();
            $itemModel->unit_price_amount = $item->unitPrice()->amount();
            $itemModel->save();
        }

        $obsoleteItems = OrderItemModel::query()
            ->where('order_id', $order->id()->value());

        if ($inventoryItemIds !== []) {
            $obsoleteItems->whereNotIn('inventory_item_id', $inventoryItemIds);
        }

        $obsoleteItems->delete();
    }

    public function findById(OrderId $orderId): ?Order
    {
        $model = OrderModel::query()->find($orderId->value());

        return $model === null ? null : $this->hydrate($model);
    }

    public function findByIdForUpdate(OrderId $orderId): ?Order
    {
        $model = OrderModel::query()
            ->whereKey($orderId->value())
            ->lockForUpdate()
            ->first();

        return $model === null ? null : $this->hydrate($model);
    }

    private function hydrate(OrderModel $model): Order
    {
        $items = OrderItemModel::query()
            ->where('order_id', $model->id)
            ->orderBy('inventory_item_id')
            ->get();

        return $this->mapper->toDomain($model, $items);
    }
}
