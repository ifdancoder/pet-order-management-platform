<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exception;

use DomainException;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;

final class DuplicateReservationItem extends DomainException
{
    public static function withId(InventoryItemId $inventoryItemId): self
    {
        return new self(sprintf(
            'Inventory item "%s" occurs more than once in the reservation.',
            $inventoryItemId->value(),
        ));
    }
}
