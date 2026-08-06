<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Command\UpdateAddress;

use Modules\Customer\Application\Data\AddressData;
use Modules\Customer\Domain\Entity\Customer;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Customer> */
final readonly class UpdateAddressCommand implements ICommand
{
    public function __construct(
        public string $identityUserId,
        public string $addressId,
        public AddressData $address,
    ) {}
}
