<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Command\MakeAddressDefault;

use Modules\Customer\Domain\Entity\Customer;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Customer> */
final readonly class MakeAddressDefaultCommand implements ICommand
{
    public function __construct(
        public string $customerId,
        public string $addressId,
    ) {}
}
