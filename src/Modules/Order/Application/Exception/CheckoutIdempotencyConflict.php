<?php

declare(strict_types=1);

namespace Modules\Order\Application\Exception;

use RuntimeException;

final class CheckoutIdempotencyConflict extends RuntimeException
{
    public static function forKey(string $idempotencyKey): self
    {
        return new self(sprintf(
            'Idempotency key "%s" was already used for another checkout request.',
            $idempotencyKey,
        ));
    }
}
