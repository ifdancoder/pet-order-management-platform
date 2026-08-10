<?php

declare(strict_types=1);

namespace Modules\Order\Application\Data;

final readonly class ShippingDetailsData
{
    public function __construct(
        public string $method,
        public string $recipientName,
        public string $line1,
        public ?string $line2,
        public string $city,
        public ?string $region,
        public string $postalCode,
        public string $countryCode,
        public int $weightGrams,
    ) {}
}
