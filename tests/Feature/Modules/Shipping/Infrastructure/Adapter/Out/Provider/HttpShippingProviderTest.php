<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Shipping\Application\Data\ShipmentBookingAttempt;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Infrastructure\Adapter\Out\Provider\HttpShippingProvider;

it('books a shipment with an idempotency key', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://carrier.example/v1/shipments' => Http::response([
            'id' => 'carrier-shipment-1',
            'tracking_number' => 'TRACK-1',
        ], 201),
    ]);
    $provider = new HttpShippingProvider(
        http: app(Factory::class),
        baseUrl: 'https://carrier.example',
        apiToken: 'test-token',
        connectTimeoutSeconds: 1,
        timeoutSeconds: 2,
    );

    $result = $provider->book(shippingProviderAttempt());

    expect($result->providerShipmentId)->toBe('carrier-shipment-1')
        ->and($result->trackingNumber)->toBe('TRACK-1');
    Http::assertSent(static fn (Request $request): bool => $request->hasHeader(
        'Idempotency-Key',
        '018f22e2-7c2a-7a33-8c4c-4ea690ad4f18',
    ) && $request['weight_grams'] === 1000);
});

function shippingProviderAttempt(): ShipmentBookingAttempt
{
    return new ShipmentBookingAttempt(
        shipmentId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f18',
        claimToken: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f20',
        orderId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f19',
        method: ShippingMethod::Courier,
        address: [
            'recipient_name' => 'Jane Doe',
            'line1' => '100 Main Street',
            'line2' => null,
            'city' => 'New York',
            'region' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
        weightGrams: 1000,
        attempts: 1,
    );
}
