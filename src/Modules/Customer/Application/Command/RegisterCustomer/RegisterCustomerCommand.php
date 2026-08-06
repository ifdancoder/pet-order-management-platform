<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Command\RegisterCustomer;

use Modules\Customer\Domain\Entity\Customer;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Customer> */
final readonly class RegisterCustomerCommand implements ICommand
{
    public function __construct(
        public string $identityUserId,
        public string $givenName,
        public string $familyName,
        public ?string $phoneNumber,
    ) {}
}
