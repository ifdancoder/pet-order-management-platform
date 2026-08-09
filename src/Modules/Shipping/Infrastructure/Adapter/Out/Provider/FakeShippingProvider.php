<?php

declare(strict_types=1);

namespace Modules\Shipping\Infrastructure\Adapter\Out\Provider;

use Modules\Shipping\Application\Data\ShipmentBookingAttempt;
use Modules\Shipping\Application\Data\ShipmentBookingResult;
use Modules\Shipping\Application\Port\Out\Provider\IShippingProvider;

final class FakeShippingProvider implements IShippingProvider
{
    public function book(ShipmentBookingAttempt $shipment): ShipmentBookingResult
    {
        $reference = strtoupper(substr(str_replace('-', '', $shipment->shipmentId), -12));

        return new ShipmentBookingResult(
            providerShipmentId: 'fake-'.$shipment->shipmentId,
            trackingNumber: 'OF'.$reference,
        );
    }
}
