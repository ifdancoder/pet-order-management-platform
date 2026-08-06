<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Exception;

use RuntimeException;

final class CustomerNotFound extends RuntimeException
{
    public static function withId(string $customerId): self
    {
        return new self(sprintf('Customer "%s" was not found.', $customerId));
    }

    public static function forIdentity(string $identityUserId): self
    {
        return new self(sprintf(
            'Customer for identity "%s" was not found.',
            $identityUserId,
        ));
    }
}
