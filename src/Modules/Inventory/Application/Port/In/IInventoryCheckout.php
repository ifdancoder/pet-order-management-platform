<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Port\In;

use Modules\Inventory\Application\Data\StockRequest;

interface IInventoryCheckout
{
    /** @param list<StockRequest> $items */
    public function isAvailable(array $items): bool;

    /** @param list<StockRequest> $items */
    public function reserve(string $reservationKey, array $items): string;
}
