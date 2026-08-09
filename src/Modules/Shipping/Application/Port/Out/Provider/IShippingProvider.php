<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Port\Out\Provider;

use Modules\Shipping\Application\Data\ShipmentBookingAttempt;
use Modules\Shipping\Application\Data\ShipmentBookingResult;

interface IShippingProvider
{
    public function book(ShipmentBookingAttempt $shipment): ShipmentBookingResult;
}
