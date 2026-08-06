<?php

declare(strict_types=1);

namespace Modules\Order\Application\Command\TransitionOrder;

use Modules\Order\Application\Enum\OrderTransition;
use Modules\Order\Application\Exception\OrderNotFound;
use Modules\Order\Application\Port\Out\Persistence\IOrderRepository;
use Modules\Order\Domain\Entity\Order;
use Modules\Order\Domain\ValueObject\OrderId;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class TransitionOrderHandler
{
    public function __construct(
        private IOrderRepository $orders,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(TransitionOrderCommand $command): Order
    {
        return $this->transaction->run(function () use ($command): Order {
            $order = $this->orders->findByIdForUpdate(
                new OrderId($command->orderId),
            ) ?? throw OrderNotFound::withId($command->orderId);

            match ($command->transition) {
                OrderTransition::Confirm => $order->confirm(),
                OrderTransition::StartProcessing => $order->startProcessing(),
                OrderTransition::MarkShipped => $order->markShipped(),
                OrderTransition::Complete => $order->complete(),
                OrderTransition::Cancel => $order->cancel(),
                OrderTransition::MarkPaymentFailed => $order->markPaymentFailed(),
                OrderTransition::RetryPayment => $order->retryPayment(),
            };

            $this->orders->save($order);

            return $order;
        });
    }
}
