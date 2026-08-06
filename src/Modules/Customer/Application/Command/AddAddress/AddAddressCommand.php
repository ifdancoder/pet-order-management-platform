<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Command\AddAddress;

use Modules\Customer\Application\Data\AddressData;
use Modules\Customer\Domain\Entity\Customer;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Customer> */
final readonly class AddAddressCommand implements ICommand
{
    public function __construct(
        public string $customerId,
        public AddressData $address,
        public bool $makeDefault,
    ) {}
}
