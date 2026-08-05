<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Exception;

use RuntimeException;

final class InvalidRefreshToken extends RuntimeException
{
    public static function create(): self
    {
        return new self('Refresh token is invalid.');
    }
}
