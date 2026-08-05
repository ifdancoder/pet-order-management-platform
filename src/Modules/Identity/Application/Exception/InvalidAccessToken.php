<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Exception;

use RuntimeException;
use Throwable;

final class InvalidAccessToken extends RuntimeException
{
    public static function create(?Throwable $previous = null): self
    {
        return new self(
            'Access token is invalid.',
            previous: $previous,
        );
    }
}
