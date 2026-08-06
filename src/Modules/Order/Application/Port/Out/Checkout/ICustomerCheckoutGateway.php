<?php

declare(strict_types=1);

namespace Modules\Order\Application\Port\Out\Checkout;

interface ICustomerCheckoutGateway
{
    public function canOrder(string $customerId): bool;
}
