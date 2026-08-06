<?php

declare(strict_types=1);

namespace Modules\Order\Application\Command\CreateOrderDraft;

use Modules\Order\Application\Port\Out\Identity\IOrderIdGenerator;
use Modules\Order\Application\Port\Out\Persistence\IOrderRepository;
use Modules\Order\Domain\Entity\Order;
use Modules\Order\Domain\ValueObject\CustomerId;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class CreateOrderDraftHandler
{
    public function __construct(
        private IOrderRepository $orders,
        private IOrderIdGenerator $orderIds,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(CreateOrderDraftCommand $command): Order
    {
        return $this->transaction->run(function () use ($command): Order {
            $order = Order::draft(
                id: $this->orderIds->generate(),
                customerId: new CustomerId($command->customerId),
                currency: $command->currency,
            );

            foreach ($command->items as $item) {
                $order->addItem($item->toOrderItem($command->currency));
            }

            $this->orders->save($order);

            return $order;
        });
    }
}
