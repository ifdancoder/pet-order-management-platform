<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Exception;

use RuntimeException;

final class PaymentNotFound extends RuntimeException
{
    public static function withId(string $paymentId): self
    {
        return new self(sprintf('Payment "%s" was not found.', $paymentId));
    }
}
