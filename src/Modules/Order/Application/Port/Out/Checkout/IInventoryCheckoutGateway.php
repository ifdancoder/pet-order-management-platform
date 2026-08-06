<?php

declare(strict_types=1);

namespace Modules\Order\Application\Port\Out\Checkout;

use Modules\Order\Application\Data\OrderItemData;

interface IInventoryCheckoutGateway
{
    /** @param list<OrderItemData> $items */
    public function isAvailable(array $items): bool;

    /** @param list<OrderItemData> $items */
    public function reserve(string $reservationKey, array $items): string;
}
