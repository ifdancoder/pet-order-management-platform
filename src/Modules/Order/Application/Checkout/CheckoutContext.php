<?php

declare(strict_types=1);

namespace Modules\Order\Application\Checkout;

use Modules\Order\Application\Data\OrderItemData;

final readonly class CheckoutContext
{
    /** @param list<OrderItemData> $items */
    public function __construct(
        public string $customerId,
        public string $currency,
        public array $items,
    ) {}
}
