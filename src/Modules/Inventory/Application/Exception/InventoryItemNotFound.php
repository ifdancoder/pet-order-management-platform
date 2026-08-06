<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Exception;

use RuntimeException;

final class InventoryItemNotFound extends RuntimeException
{
    public static function withId(string $inventoryItemId): self
    {
        return new self(sprintf(
            'Inventory item "%s" was not found.',
            $inventoryItemId,
        ));
    }
}
