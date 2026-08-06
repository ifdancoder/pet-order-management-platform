<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Data;

use Modules\Inventory\Domain\ValueObject\InventoryItemId;
use Modules\Inventory\Domain\ValueObject\ReservationLine;

final readonly class StockRequest
{
    public function __construct(
        public string $inventoryItemId,
        public int $quantity,
    ) {}

    public function toReservationLine(): ReservationLine
    {
        return new ReservationLine(
            new InventoryItemId($this->inventoryItemId),
            $this->quantity,
        );
    }
}
