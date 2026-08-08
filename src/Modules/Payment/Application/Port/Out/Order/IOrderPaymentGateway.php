<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Port\Out\Order;

use Modules\Payment\Application\Data\PayableOrder;

interface IOrderPaymentGateway
{
    public function findPayableOrder(
        string $orderId,
        string $identityUserId,
    ): ?PayableOrder;
}
