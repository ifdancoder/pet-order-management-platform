<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Exception;

use DomainException;
use Modules\Customer\Domain\ValueObject\AddressId;

final class AddressAlreadyExists extends DomainException
{
    public static function withId(AddressId $addressId): self
    {
        return new self(sprintf(
            'Address "%s" already exists.',
            $addressId->value(),
        ));
    }
}
