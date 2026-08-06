<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Command\RemoveAddress;

use Modules\Customer\Domain\Entity\Customer;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Customer> */
final readonly class RemoveAddressCommand implements ICommand
{
    public function __construct(
        public string $identityUserId,
        public string $addressId,
    ) {}
}
