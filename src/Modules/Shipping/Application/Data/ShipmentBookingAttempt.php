<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Data;

use Modules\Shipping\Domain\Enum\ShippingMethod;

final readonly class ShipmentBookingAttempt
{
    /** @param array{recipient_name: string, line1: string, line2: ?string, city: string, region: ?string, postal_code: string, country_code: string} $address */
    public function __construct(
        public string $shipmentId,
        public string $claimToken,
        public string $orderId,
        public ShippingMethod $method,
        public array $address,
        public int $weightGrams,
        public int $attempts,
    ) {}
}
