<?php

declare(strict_types=1);

namespace Modules\Return\Infrastructure\Adapter\Out\Customer;

use Modules\Customer\Application\Port\In\ICustomerIdentityLookup;
use Modules\Return\Application\Port\Out\Customer\IReturnCustomerGateway;

final readonly class CustomerIdentityGateway implements IReturnCustomerGateway
{
    public function __construct(
        private ICustomerIdentityLookup $customers,
    ) {}

    public function customerIdForIdentity(string $identityUserId): ?string
    {
        return $this->customers->customerIdForIdentity($identityUserId);
    }
}
