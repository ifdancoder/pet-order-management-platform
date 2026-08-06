<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Query\GetCustomerByIdentity;

use Modules\Customer\Application\Exception\CustomerNotFound;
use Modules\Customer\Application\Port\Out\Persistence\ICustomerRepository;
use Modules\Customer\Domain\Entity\Customer;
use Modules\Customer\Domain\ValueObject\IdentityUserId;

final readonly class GetCustomerByIdentityHandler
{
    public function __construct(
        private ICustomerRepository $customers,
    ) {}

    public function __invoke(GetCustomerByIdentityQuery $query): Customer
    {
        return $this->customers->findByIdentityUserId(
            new IdentityUserId($query->identityUserId),
        ) ?? throw CustomerNotFound::forIdentity($query->identityUserId);
    }
}
