<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exception;

use DomainException;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;

final class InsufficientStock extends DomainException
{
    public static function forItem(
        InventoryItemId $inventoryItemId,
        int $requested,
        int $available,
    ): self {
        return new self(sprintf(
            'Inventory item "%s" has %d units available; %d requested.',
            $inventoryItemId->value(),
            $available,
            $requested,
        ));
    }
}
