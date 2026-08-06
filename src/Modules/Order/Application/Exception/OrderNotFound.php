<?php

declare(strict_types=1);

namespace Modules\Order\Application\Exception;

use RuntimeException;

final class OrderNotFound extends RuntimeException
{
    public static function withId(string $orderId): self
    {
        return new self(sprintf('Order "%s" was not found.', $orderId));
    }
}
