<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Exception;

use DomainException;
use Modules\Order\Domain\ValueObject\InventoryItemId;

final class OrderItemNotFound extends DomainException
{
    public static function withInventoryItemId(
        InventoryItemId $inventoryItemId,
    ): self {
        return new self(sprintf(
            'Inventory item "%s" was not found in the order.',
            $inventoryItemId->value(),
        ));
    }
}
