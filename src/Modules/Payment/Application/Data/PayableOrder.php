<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Data;

final readonly class PayableOrder
{
    public function __construct(
        public string $orderId,
        public int $amount,
        public string $currency,
    ) {}
}
