<?php

declare(strict_types=1);

namespace Modules\Order\Application\Data;

final readonly class CheckoutResult
{
    public function __construct(
        public string $orderId,
        public string $inventoryReservationId,
    ) {}
}
