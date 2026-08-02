<?php

declare(strict_types=1);

namespace App\Layer\Identity\Domain\Exception;

use DomainException;

final class InvalidPasswordHash extends DomainException
{
    public static function empty(): self
    {
        return new self('Password hash cannot be empty.');
    }
}
