<?php

declare(strict_types=1);

namespace App\Layer\Identity\Domain\Exception;

use DomainException;

final class UserDisabled extends DomainException
{
    public static function cannotActivate(): self
    {
        return new self('Disabled user cannot be activated.');
    }

    public static function cannotSuspend(): self
    {
        return new self('Disabled user cannot be suspended.');
    }

    public static function cannotChangeEmail(): self
    {
        return new self(
            'Email of a disabled user cannot be changed.',
        );
    }

    public static function cannotChangePassword(): self
    {
        return new self(
            'Password of a disabled user cannot be changed.',
        );
    }
}
