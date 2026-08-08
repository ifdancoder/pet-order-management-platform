<?php

declare(strict_types=1);

namespace Modules\Order\Application\Data;

final readonly class OrderPaymentDetails
{
    public function __construct(
        public string $orderId,
        public int $amount,
        public string $currency,
    ) {}
}
