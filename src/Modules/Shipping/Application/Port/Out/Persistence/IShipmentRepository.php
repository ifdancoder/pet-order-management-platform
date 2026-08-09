<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Port\Out\Persistence;

use Modules\Shipping\Application\Data\ShipmentBookingAttempt;
use Modules\Shipping\Domain\Entity\Shipment;

interface IShipmentRepository
{
    public function save(Shipment $shipment): void;

    public function findByOrderId(string $orderId): ?Shipment;

    /** @return list<ShipmentBookingAttempt> */
    public function claimBatch(int $limit, int $claimTimeoutSeconds): array;

    public function markBooked(
        string $shipmentId,
        string $claimToken,
        string $providerShipmentId,
        string $trackingNumber,
    ): void;

    public function release(
        string $shipmentId,
        string $claimToken,
        string $error,
        int $delaySeconds,
        bool $terminal,
    ): void;
}
