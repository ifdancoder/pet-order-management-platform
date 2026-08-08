<?php

declare(strict_types=1);

namespace Modules\Order\Application\Payment;

use InvalidArgumentException;
use Modules\Order\Application\Data\OrderPaymentDetails;
use Modules\Order\Application\Port\In\IOrderPayment;
use Modules\Order\Application\Port\Out\Persistence\IOrderRepository;
use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Domain\ValueObject\OrderId;

final readonly class OrderPayment implements IOrderPayment
{
    public function __construct(
        private IOrderRepository $orders,
    ) {}

    public function findPayableOrder(
        string $orderId,
        string $customerId,
    ): ?OrderPaymentDetails {
        try {
            $order = $this->orders->findById(new OrderId($orderId));
        } catch (InvalidArgumentException) {
            return null;
        }

        if (
            $order === null
            || $order->customerId()->value() !== $customerId
            || $order->status() !== OrderStatus::Placed
        ) {
            return null;
        }

        return new OrderPaymentDetails(
            orderId: $order->id()->value(),
            amount: $order->total()->amount(),
            currency: $order->currency(),
        );
    }
}
