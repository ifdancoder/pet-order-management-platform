<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Exception;

use RuntimeException;
use Throwable;

final class EmailAlreadyExists extends RuntimeException
{
    public static function withEmail(
        string $email,
        ?Throwable $previous = null,
    ): self {
        return new self(
            sprintf('User with email "%s" already exists.', $email),
            previous: $previous,
        );
    }
}
