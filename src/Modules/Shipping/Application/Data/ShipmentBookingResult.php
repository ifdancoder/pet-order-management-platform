<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Data;

final readonly class ShipmentBookingResult
{
    public function __construct(
        public string $providerShipmentId,
        public string $trackingNumber,
    ) {}
}
