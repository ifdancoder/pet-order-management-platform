<?php

declare(strict_types=1);

namespace Modules\Return\Application\Exception;

use RuntimeException;

final class ReturnAlreadyExists extends RuntimeException
{
    public static function forOrder(string $orderId): self
    {
        return new self(sprintf('A return already exists for order %s.', $orderId));
    }
}
