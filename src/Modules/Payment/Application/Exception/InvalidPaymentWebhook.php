<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Exception;

use RuntimeException;
use Throwable;

final class InvalidPaymentWebhook extends RuntimeException
{
    public static function create(?Throwable $previous = null): self
    {
        return new self('Payment webhook is invalid.', previous: $previous);
    }
}
