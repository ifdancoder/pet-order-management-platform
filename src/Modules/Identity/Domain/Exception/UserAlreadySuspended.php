<?php

declare(strict_types=1);

namespace src\Layer\Identity\Domain\Exception;

use DomainException;

final class UserAlreadySuspended extends DomainException
{
    public static function create(): self
    {
        return new self('User is already suspended.');
    }
}
