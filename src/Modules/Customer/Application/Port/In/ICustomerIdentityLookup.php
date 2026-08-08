<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Port\In;

interface ICustomerIdentityLookup
{
    public function customerIdForIdentity(string $identityUserId): ?string;
}
