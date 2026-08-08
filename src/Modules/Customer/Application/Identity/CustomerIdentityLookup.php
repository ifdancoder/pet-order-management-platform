<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Identity;

use InvalidArgumentException;
use Modules\Customer\Application\Port\In\ICustomerIdentityLookup;
use Modules\Customer\Application\Port\Out\Persistence\ICustomerRepository;
use Modules\Customer\Domain\ValueObject\IdentityUserId;

final readonly class CustomerIdentityLookup implements ICustomerIdentityLookup
{
    public function __construct(
        private ICustomerRepository $customers,
    ) {}

    public function customerIdForIdentity(string $identityUserId): ?string
    {
        try {
            $customer = $this->customers->findByIdentityUserId(
                new IdentityUserId($identityUserId),
            );
        } catch (InvalidArgumentException) {
            return null;
        }

        return $customer?->id()->value();
    }
}
