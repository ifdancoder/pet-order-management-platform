<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Port\In;

interface ICustomerCheckout
{
    public function canOrder(string $customerId): bool;
}
