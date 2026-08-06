<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Query\GetCustomer;

use Modules\Customer\Domain\Entity\Customer;
use Shared\Application\Bus\Query\IQuery;

/** @implements IQuery<Customer> */
final readonly class GetCustomerQuery implements IQuery
{
    public function __construct(
        public string $customerId,
    ) {}
}
