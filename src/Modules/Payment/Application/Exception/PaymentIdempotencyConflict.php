<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Exception;

use RuntimeException;

final class PaymentIdempotencyConflict extends RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf(
            'Idempotency key "%s" was already used for another payment request.',
            $key,
        ));
    }
}
