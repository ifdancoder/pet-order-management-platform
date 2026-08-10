<?php

declare(strict_types=1);

namespace Modules\Return\Application\Port\Out\Customer;

interface IReturnCustomerGateway
{
    public function customerIdForIdentity(string $identityUserId): ?string;
}
