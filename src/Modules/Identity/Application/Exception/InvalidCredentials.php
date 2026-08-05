<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Exception;

use RuntimeException;

final class InvalidCredentials extends RuntimeException
{
    public static function create(): self
    {
        return new self('The supplied credentials are invalid.');
    }
}
