<?php

declare(strict_types=1);

namespace Modules\Order\Application\Exception;

use RuntimeException;

final class CheckoutInProgress extends RuntimeException
{
    public static function forKey(string $idempotencyKey): self
    {
        return new self(sprintf(
            'Checkout with idempotency key "%s" is still being processed.',
            $idempotencyKey,
        ));
    }
}
