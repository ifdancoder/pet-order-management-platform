<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Order;

use Modules\Customer\Application\Port\In\ICustomerIdentityLookup;
use Modules\Order\Application\Port\In\IOrderPayment;
use Modules\Payment\Application\Data\PayableOrder;
use Modules\Payment\Application\Port\Out\Order\IOrderPaymentGateway;

final readonly class OrderPaymentGateway implements IOrderPaymentGateway
{
    public function __construct(
        private ICustomerIdentityLookup $customers,
        private IOrderPayment $orders,
    ) {}

    public function findPayableOrder(
        string $orderId,
        string $identityUserId,
    ): ?PayableOrder {
        $customerId = $this->customers->customerIdForIdentity($identityUserId);

        if ($customerId === null) {
            return null;
        }

        $order = $this->orders->findPayableOrder($orderId, $customerId);

        if ($order === null) {
            return null;
        }

        return new PayableOrder(
            orderId: $order->orderId,
            amount: $order->amount,
            currency: $order->currency,
        );
    }
}
