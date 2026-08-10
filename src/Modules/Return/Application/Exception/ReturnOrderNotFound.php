<?php

declare(strict_types=1);

namespace Modules\Return\Application\Exception;

use RuntimeException;

final class ReturnOrderNotFound extends RuntimeException
{
    public static function withId(string $orderId): self
    {
        return new self(sprintf('Order %s is unavailable for this customer.', $orderId));
    }
}
