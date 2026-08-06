<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Exception;

use DomainException;

final class CustomerArchived extends DomainException
{
    public static function cannotBeChanged(): self
    {
        return new self('An archived customer cannot be changed.');
    }
}
