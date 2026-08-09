<?php

declare(strict_types=1);

namespace Modules\Shipping\Infrastructure\Adapter\Out\Provider;

use Illuminate\Http\Client\Factory;
use Modules\Shipping\Application\Data\ShipmentBookingAttempt;
use Modules\Shipping\Application\Data\ShipmentBookingResult;
use Modules\Shipping\Application\Port\Out\Provider\IShippingProvider;
use RuntimeException;

final readonly class HttpShippingProvider implements IShippingProvider
{
    public function __construct(
        private Factory $http,
        private string $baseUrl,
        private string $apiToken,
        private int $connectTimeoutSeconds,
        private int $timeoutSeconds,
    ) {}

    public function book(ShipmentBookingAttempt $shipment): ShipmentBookingResult
    {
        $response = $this->http
            ->baseUrl($this->baseUrl)
            ->withToken($this->apiToken)
            ->withHeaders(['Idempotency-Key' => $shipment->shipmentId])
            ->connectTimeout($this->connectTimeoutSeconds)
            ->timeout($this->timeoutSeconds)
            ->post('/v1/shipments', [
                'order_id' => $shipment->orderId,
                'method' => $shipment->method->value,
                'address' => $shipment->address,
                'weight_grams' => $shipment->weightGrams,
            ])
            ->throw();
        $providerShipmentId = $response->json('id');
        $trackingNumber = $response->json('tracking_number');

        if (! is_string($providerShipmentId) || ! is_string($trackingNumber)) {
            throw new RuntimeException('Shipping provider response is invalid.');
        }

        return new ShipmentBookingResult(
            providerShipmentId: $providerShipmentId,
            trackingNumber: $trackingNumber,
        );
    }
}
