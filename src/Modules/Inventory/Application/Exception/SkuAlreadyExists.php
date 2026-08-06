<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Exception;

use RuntimeException;
use Throwable;

final class SkuAlreadyExists extends RuntimeException
{
    public static function withSku(
        string $sku,
        ?Throwable $previous = null,
    ): self {
        return new self(
            sprintf('Inventory item with SKU "%s" already exists.', $sku),
            previous: $previous,
        );
    }
}
