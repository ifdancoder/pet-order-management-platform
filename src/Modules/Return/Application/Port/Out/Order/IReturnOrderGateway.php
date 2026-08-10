<?php

declare(strict_types=1);

namespace Modules\Return\Application\Port\Out\Order;

use Modules\Return\Application\Data\ReturnableOrder;

interface IReturnOrderGateway
{
    public function findForIdentity(
        string $orderId,
        string $identityUserId,
    ): ?ReturnableOrder;
}
