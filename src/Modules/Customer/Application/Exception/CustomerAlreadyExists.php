<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Exception;

use RuntimeException;
use Throwable;

final class CustomerAlreadyExists extends RuntimeException
{
    public static function forIdentity(
        string $identityUserId,
        ?Throwable $previous = null,
    ): self {
        return new self(
            sprintf('Customer for identity "%s" already exists.', $identityUserId),
            previous: $previous,
        );
    }
}
