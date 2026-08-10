<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Command\CreateShipment;

use Modules\Shipping\Application\Port\Out\Identity\IShipmentIdGenerator;
use Modules\Shipping\Application\Port\Out\Persistence\IShipmentRepository;
use Modules\Shipping\Domain\Entity\Shipment;
use Modules\Shipping\Domain\ValueObject\ShippingAddress;
use Shared\Application\Port\Out\Transaction\ITransactionManager;
use Shared\Domain\ValueObject\Money;

final readonly class CreateShipmentHandler
{
    public function __construct(
        private IShipmentRepository $shipments,
        private IShipmentIdGenerator $ids,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(CreateShipmentCommand $command): Shipment
    {
        return $this->transaction->run(function () use ($command): Shipment {
            $existing = $this->shipments->findByOrderId($command->orderId);

            if ($existing !== null) {
                return $existing;
            }

            $shipment = Shipment::awaitingPayment(
                id: $this->ids->generate(),
                orderId: $command->orderId,
                method: $command->method,
                address: new ShippingAddress(
                    recipientName: $command->recipientName,
                    line1: $command->line1,
                    line2: $command->line2,
                    city: $command->city,
                    region: $command->region,
                    postalCode: $command->postalCode,
                    countryCode: $command->countryCode,
                ),
                weightGrams: $command->weightGrams,
                cost: new Money($command->costAmount, $command->currency),
            );
            $this->shipments->save($shipment);

            return $shipment;
        });
    }
}
