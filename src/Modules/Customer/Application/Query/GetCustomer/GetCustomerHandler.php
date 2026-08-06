<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Query\GetCustomer;

use Modules\Customer\Application\Exception\CustomerNotFound;
use Modules\Customer\Application\Port\Out\Persistence\ICustomerRepository;
use Modules\Customer\Domain\Entity\Customer;
use Modules\Customer\Domain\ValueObject\CustomerId;

final readonly class GetCustomerHandler
{
    public function __construct(
        private ICustomerRepository $customers,
    ) {}

    public function __invoke(GetCustomerQuery $query): Customer
    {
        return $this->customers->findById(new CustomerId($query->customerId))
            ?? throw CustomerNotFound::withId($query->customerId);
    }
}
