<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Exception;

use RuntimeException;

final class PaymentNotAllowed extends RuntimeException
{
    public static function forOrder(string $orderId): self
    {
        return new self(sprintf(
            'Order "%s" cannot be paid by this identity.',
            $orderId,
        ));
    }
}
