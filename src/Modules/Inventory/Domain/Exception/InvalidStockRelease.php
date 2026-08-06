<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exception;

use DomainException;

final class InvalidStockRelease extends DomainException
{
    public static function exceedsReserved(int $quantity, int $reserved): self
    {
        return new self(sprintf(
            'Cannot release %d units when only %d are reserved.',
            $quantity,
            $reserved,
        ));
    }
}
