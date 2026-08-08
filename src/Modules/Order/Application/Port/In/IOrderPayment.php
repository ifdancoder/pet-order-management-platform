<?php

declare(strict_types=1);

namespace Modules\Order\Application\Port\In;

use Modules\Order\Application\Data\OrderPaymentDetails;

interface IOrderPayment
{
    public function findPayableOrder(
        string $orderId,
        string $customerId,
    ): ?OrderPaymentDetails;
}
