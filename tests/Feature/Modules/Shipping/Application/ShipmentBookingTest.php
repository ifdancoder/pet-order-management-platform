<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Shipping\Application\Booking\ShipmentDispatcher;
use Modules\Shipping\Application\Command\CreateShipment\CreateShipmentCommand;
use Modules\Shipping\Application\Data\ShipmentBookingAttempt;
use Modules\Shipping\Application\Data\ShipmentBookingResult;
use Modules\Shipping\Application\Port\Out\Persistence\IShipmentRepository;
use Modules\Shipping\Application\Port\Out\Provider\IShippingProvider;
use Modules\Shipping\Domain\Enum\ShipmentStatus;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ShipmentModel;
use Shared\Application\Bus\Command\ICommandBus;

uses(LazilyRefreshDatabase::class);

it('creates one pending shipment per order', function (): void {
    $command = shipmentCommand();
    $commandBus = app(ICommandBus::class);

    $first = $commandBus->dispatch($command);
    $second = $commandBus->dispatch($command);

    expect($second->id()->value())->toBe($first->id()->value());
    $this->assertDatabaseCount('shipments', 1);
    $this->assertDatabaseHas('shipments', [
        'id' => $first->id()->value(),
        'order_id' => $command->orderId,
        'method' => ShippingMethod::Courier->value,
        'status' => ShipmentStatus::Pending->value,
        'cost_amount' => 650,
    ]);
});

it('books a pending shipment through the configured provider', function (): void {
    $shipment = app(ICommandBus::class)->dispatch(shipmentCommand());

    $result = app(ShipmentDispatcher::class)->dispatchPending();

    expect($result->claimed)->toBe(1)
        ->and($result->booked)->toBe(1)
        ->and($result->retrying)->toBe(0)
        ->and($result->failed)->toBe(0);
    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->id()->value(),
        'status' => ShipmentStatus::Booked->value,
        'provider_shipment_id' => 'fake-'.$shipment->id()->value(),
    ]);
    expect(ShipmentModel::query()->sole()->tracking_number)->toStartWith('OF');
});

it('releases a failed booking for a delayed retry', function (): void {
    $this->travelTo('2026-09-28 08:00:00');
    app(ICommandBus::class)->dispatch(shipmentCommand());
    $provider = new class implements IShippingProvider
    {
        public function book(ShipmentBookingAttempt $shipment): ShipmentBookingResult
        {
            throw new RuntimeException('Provider unavailable');
        }
    };
    $dispatcher = new ShipmentDispatcher(
        shipments: app(IShipmentRepository::class),
        provider: $provider,
        batchSize: 10,
        claimTimeoutSeconds: 60,
        maximumAttempts: 3,
        initialRetryDelaySeconds: 5,
        maximumRetryDelaySeconds: 60,
    );

    $result = $dispatcher->dispatchPending();

    expect($result->retrying)->toBe(1);
    $this->assertDatabaseHas('shipments', [
        'status' => ShipmentStatus::Pending->value,
        'attempts' => 1,
        'last_error' => 'RuntimeException: Provider unavailable',
        'available_at' => '2026-09-28 08:00:05',
    ]);
});

function shipmentCommand(): CreateShipmentCommand
{
    return new CreateShipmentCommand(
        orderId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f19',
        method: ShippingMethod::Courier,
        recipientName: 'Jane Doe',
        line1: '100 Main Street',
        line2: null,
        city: 'New York',
        region: 'NY',
        postalCode: '10001',
        countryCode: 'US',
        weightGrams: 1000,
        costAmount: 650,
        currency: 'USD',
    );
}
