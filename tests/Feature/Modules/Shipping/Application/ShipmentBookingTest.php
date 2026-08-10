<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Shipping\Application\Booking\ShipmentDispatcher;
use Modules\Shipping\Application\Command\CreateShipment\CreateShipmentCommand;
use Modules\Shipping\Application\Data\ShipmentBookingAttempt;
use Modules\Shipping\Application\Data\ShipmentBookingResult;
use Modules\Shipping\Application\Messaging\PaymentCapturedShipmentHandler;
use Modules\Shipping\Application\Port\Out\Persistence\IShipmentRepository;
use Modules\Shipping\Application\Port\Out\Provider\IShippingProvider;
use Modules\Shipping\Domain\Enum\ShipmentStatus;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ShipmentModel;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Shared\Application\Bus\Command\ICommandBus;
use Shared\Application\Messaging\IntegrationMessage;

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
        'status' => ShipmentStatus::AwaitingPayment->value,
        'cost_amount' => 650,
    ]);
});

it('books a pending shipment through the configured provider', function (): void {
    $shipment = app(ICommandBus::class)->dispatch(shipmentCommand());
    app(PaymentCapturedShipmentHandler::class)->handle(paymentCapturedMessage());

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

it('logs a structured event when a shipment is booked', function (): void {
    app(ICommandBus::class)->dispatch(shipmentCommand());
    app(PaymentCapturedShipmentHandler::class)->handle(paymentCapturedMessage());
    $logger = new ShipmentDispatcherTestLogger;
    app()->instance(LoggerInterface::class, $logger);

    app(ShipmentDispatcher::class)->dispatchPending();

    expect($logger->records)->toHaveCount(1)
        ->and($logger->records[0]['message'])->toBe('Shipment booking succeeded.')
        ->and($logger->records[0]['context'])->toHaveKey('shipment_id');
});

it('does not book a shipment before payment is captured', function (): void {
    app(ICommandBus::class)->dispatch(shipmentCommand());

    $result = app(ShipmentDispatcher::class)->dispatchPending();

    expect($result->claimed)->toBe(0)
        ->and($result->booked)->toBe(0);
    $this->assertDatabaseHas('shipments', [
        'status' => ShipmentStatus::AwaitingPayment->value,
        'attempts' => 0,
        'provider_shipment_id' => null,
    ]);
});

it('handles repeated payment capture facts without resetting shipment state', function (): void {
    app(ICommandBus::class)->dispatch(shipmentCommand());
    $handler = app(PaymentCapturedShipmentHandler::class);

    $handler->handle(paymentCapturedMessage());
    $handler->handle(paymentCapturedMessage());

    $this->assertDatabaseHas('shipments', [
        'status' => ShipmentStatus::Pending->value,
        'attempts' => 0,
    ]);
});

it('releases a failed booking for a delayed retry', function (): void {
    $this->travelTo('2026-09-28 08:00:00');
    app(ICommandBus::class)->dispatch(shipmentCommand());
    app(PaymentCapturedShipmentHandler::class)->handle(paymentCapturedMessage());
    $provider = new class implements IShippingProvider
    {
        public function book(ShipmentBookingAttempt $shipment): ShipmentBookingResult
        {
            throw new RuntimeException('Provider unavailable');
        }
    };
    $logger = new ShipmentDispatcherTestLogger;
    $dispatcher = new ShipmentDispatcher(
        shipments: app(IShipmentRepository::class),
        provider: $provider,
        logger: $logger,
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
    expect($logger->records)->toHaveCount(1)
        ->and($logger->records[0]['message'])->toBe('Shipment booking retrying.')
        ->and($logger->records[0]['context'])->toHaveKeys(['shipment_id', 'attempts', 'exception']);
});

final class ShipmentDispatcherTestLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string, context: array<string, mixed>}> */
    public array $records = [];

    /** @param array<string, mixed> $context */
    public function log(
        mixed $level,
        Stringable|string $message,
        array $context = [],
    ): void {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}

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

function paymentCapturedMessage(): IntegrationMessage
{
    return new IntegrationMessage(
        messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f21',
        name: 'payment.captured.v1',
        occurredAt: new DateTimeImmutable('2026-09-28T08:00:00+00:00'),
        aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f22',
        data: ['order_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f19'],
    );
}
