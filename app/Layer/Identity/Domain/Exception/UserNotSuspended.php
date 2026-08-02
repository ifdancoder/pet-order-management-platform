<?php

declare(strict_types=1);

namespace App\Layer\Identity\Domain\Exception;

use DomainException;

final class UserNotSuspended extends DomainException
{
    public static function create(): self
    {
        return new self('User is not suspended.');
    }
}
