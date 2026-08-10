<?php

declare(strict_types=1);

namespace Modules\Order\Application\Port\In;

use Modules\Order\Application\Data\ReturnableOrder;

interface IOrderReturnLookup
{
    public function findForCustomer(
        string $orderId,
        string $customerId,
    ): ?ReturnableOrder;
}
