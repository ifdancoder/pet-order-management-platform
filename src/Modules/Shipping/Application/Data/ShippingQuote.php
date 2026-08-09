<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Data;

use Modules\Shipping\Domain\Enum\ShippingMethod;

final readonly class ShippingQuote
{
    public function __construct(
        public ShippingMethod $method,
        public int $amount,
        public string $currency,
    ) {}
}
