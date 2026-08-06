<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Exception;

use DomainException;
use Modules\Customer\Domain\ValueObject\AddressId;

final class AddressNotFound extends DomainException
{
    public static function withId(AddressId $addressId): self
    {
        return new self(sprintf(
            'Address "%s" was not found.',
            $addressId->value(),
        ));
    }
}
