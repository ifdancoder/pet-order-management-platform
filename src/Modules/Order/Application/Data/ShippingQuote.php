<?php

declare(strict_types=1);

namespace Modules\Order\Application\Data;

final readonly class ShippingQuote
{
    public function __construct(
        public string $method,
        public int $amount,
        public string $currency,
    ) {}
}
