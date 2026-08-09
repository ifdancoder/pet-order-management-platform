<?php

declare(strict_types=1);

use Modules\Shipping\Domain\Entity\Shipment;
use Modules\Shipping\Domain\Enum\ShipmentStatus;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Domain\Exception\InvalidShipmentTransition;
use Modules\Shipping\Domain\ValueObject\ShipmentId;
use Modules\Shipping\Domain\ValueObject\ShippingAddress;
use Shared\Domain\ValueObject\Money;

it('moves a pending shipment to booked with provider references', function (): void {
    $shipment = pendingShipment();

    $shipment->book('provider-shipment-1', 'TRACK-1');

    expect($shipment->status())->toBe(ShipmentStatus::Booked)
        ->and($shipment->providerShipmentId())->toBe('provider-shipment-1')
        ->and($shipment->trackingNumber())->toBe('TRACK-1');
});

it('does not book a terminal shipment twice', function (): void {
    $shipment = pendingShipment();
    $shipment->fail();

    $action = fn () => $shipment->book('provider-shipment-1', 'TRACK-1');

    expect($action)->toThrow(InvalidShipmentTransition::class);
});

function pendingShipment(): Shipment
{
    return Shipment::pending(
        id: new ShipmentId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f18'),
        orderId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f19',
        method: ShippingMethod::Courier,
        address: new ShippingAddress(
            recipientName: 'Jane Doe',
            line1: '100 Main Street',
            line2: null,
            city: 'New York',
            region: 'NY',
            postalCode: '10001',
            countryCode: 'US',
        ),
        weightGrams: 1000,
        cost: new Money(650, 'USD'),
    );
}
