<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Data;

use Modules\Shipping\Domain\Enum\ShippingMethod;

final readonly class ShippingQuoteRequest
{
    public function __construct(
        public ShippingMethod $method,
        public string $countryCode,
        public string $postalCode,
        public int $weightGrams,
        public string $currency,
    ) {}
}
