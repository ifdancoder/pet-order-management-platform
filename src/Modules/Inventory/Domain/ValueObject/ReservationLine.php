<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObject;

use InvalidArgumentException;

final readonly class ReservationLine
{
    public function __construct(
        private InventoryItemId $inventoryItemId,
        private int $quantity,
    ) {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Reservation quantity must be positive.');
        }
    }

    public function inventoryItemId(): InventoryItemId
    {
        return $this->inventoryItemId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }
}
