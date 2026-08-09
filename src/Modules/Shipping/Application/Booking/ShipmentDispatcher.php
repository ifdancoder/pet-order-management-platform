<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Booking;

use Modules\Shipping\Application\Data\ShipmentDispatchResult;
use Modules\Shipping\Application\Port\Out\Persistence\IShipmentRepository;
use Modules\Shipping\Application\Port\Out\Provider\IShippingProvider;
use Throwable;

final readonly class ShipmentDispatcher
{
    public function __construct(
        private IShipmentRepository $shipments,
        private IShippingProvider $provider,
        private int $batchSize,
        private int $claimTimeoutSeconds,
        private int $maximumAttempts,
        private int $initialRetryDelaySeconds,
        private int $maximumRetryDelaySeconds,
    ) {}

    public function dispatchPending(): ShipmentDispatchResult
    {
        $shipments = $this->shipments->claimBatch(
            $this->batchSize,
            $this->claimTimeoutSeconds,
        );
        $booked = 0;
        $retrying = 0;
        $failed = 0;

        foreach ($shipments as $shipment) {
            try {
                $result = $this->provider->book($shipment);
                $this->shipments->markBooked(
                    shipmentId: $shipment->shipmentId,
                    claimToken: $shipment->claimToken,
                    providerShipmentId: $result->providerShipmentId,
                    trackingNumber: $result->trackingNumber,
                );
                $booked++;
            } catch (Throwable $exception) {
                $terminal = $shipment->attempts >= $this->maximumAttempts;
                $this->shipments->release(
                    shipmentId: $shipment->shipmentId,
                    claimToken: $shipment->claimToken,
                    error: $exception::class.': '.$exception->getMessage(),
                    delaySeconds: $this->retryDelay($shipment->attempts),
                    terminal: $terminal,
                );
                $terminal ? $failed++ : $retrying++;
            }
        }

        return new ShipmentDispatchResult(
            claimed: count($shipments),
            booked: $booked,
            retrying: $retrying,
            failed: $failed,
        );
    }

    private function retryDelay(int $attempts): int
    {
        $exponent = min(max($attempts - 1, 0), 20);

        return min(
            $this->initialRetryDelaySeconds * (2 ** $exponent),
            $this->maximumRetryDelaySeconds,
        );
    }
}
