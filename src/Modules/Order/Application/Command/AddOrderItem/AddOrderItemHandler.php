<?php

declare(strict_types=1);

namespace Modules\Order\Application\Command\AddOrderItem;

use Modules\Order\Application\Exception\OrderNotFound;
use Modules\Order\Application\Port\Out\Persistence\IOrderRepository;
use Modules\Order\Domain\Entity\Order;
use Modules\Order\Domain\ValueObject\OrderId;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class AddOrderItemHandler
{
    public function __construct(
        private IOrderRepository $orders,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(AddOrderItemCommand $command): Order
    {
        return $this->transaction->run(function () use ($command): Order {
            $order = $this->orders->findByIdForUpdate(
                new OrderId($command->orderId),
            ) ?? throw OrderNotFound::withId($command->orderId);

            $order->addItem($command->item->toOrderItem($order->currency()));
            $this->orders->save($order);

            return $order;
        });
    }
}
