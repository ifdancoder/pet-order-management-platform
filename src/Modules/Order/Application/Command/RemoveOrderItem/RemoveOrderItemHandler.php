<?php

declare(strict_types=1);

namespace Modules\Order\Application\Command\RemoveOrderItem;

use Modules\Order\Application\Exception\OrderNotFound;
use Modules\Order\Application\Port\Out\Persistence\IOrderRepository;
use Modules\Order\Domain\Entity\Order;
use Modules\Order\Domain\ValueObject\InventoryItemId;
use Modules\Order\Domain\ValueObject\OrderId;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class RemoveOrderItemHandler
{
    public function __construct(
        private IOrderRepository $orders,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(RemoveOrderItemCommand $command): Order
    {
        return $this->transaction->run(function () use ($command): Order {
            $order = $this->orders->findByIdForUpdate(
                new OrderId($command->orderId),
            ) ?? throw OrderNotFound::withId($command->orderId);

            $order->removeItem(new InventoryItemId($command->inventoryItemId));
            $this->orders->save($order);

            return $order;
        });
    }
}
