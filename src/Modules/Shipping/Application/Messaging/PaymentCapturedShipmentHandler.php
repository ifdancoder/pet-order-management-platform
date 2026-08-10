<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Messaging;

use InvalidArgumentException;
use Modules\Shipping\Application\Exception\ShipmentNotFound;
use Modules\Shipping\Application\Port\Out\Persistence\IShipmentRepository;
use Modules\Shipping\Domain\Enum\ShipmentStatus;
use Shared\Application\Messaging\IntegrationMessage;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;

final readonly class PaymentCapturedShipmentHandler implements IIntegrationMessageHandler
{
    public function __construct(
        private IShipmentRepository $shipments,
    ) {}

    public function consumerName(): string
    {
        return 'shipping-payment-captured';
    }

    public function messageNames(): array
    {
        return ['payment.captured.v1'];
    }

    public function handle(IntegrationMessage $message): void
    {
        $orderId = $message->data['order_id'] ?? null;

        if (! is_string($orderId)) {
            throw new InvalidArgumentException(
                'Payment captured message order_id must be a string.',
            );
        }

        $shipment = $this->shipments->findByOrderIdForUpdate($orderId)
            ?? throw ShipmentNotFound::forOrder($orderId);

        if ($shipment->status() !== ShipmentStatus::AwaitingPayment) {
            return;
        }

        $shipment->markReadyForBooking();
        $this->shipments->save($shipment);
    }
}
