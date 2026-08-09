<?php

declare(strict_types=1);

use Modules\Shipping\Domain\Contract\IShippingCostStrategy;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Domain\Exception\DuplicateShippingStrategy;
use Modules\Shipping\Domain\Exception\ShippingMethodUnavailable;
use Modules\Shipping\Domain\Exception\ShippingStrategyNotFound;
use Modules\Shipping\Domain\Service\ShippingCostCalculator;
use Modules\Shipping\Domain\Service\ShippingRegionPolicy;
use Modules\Shipping\Domain\Service\ShippingStrategyResolver;
use Modules\Shipping\Domain\Strategy\CourierShippingStrategy;
use Modules\Shipping\Domain\Strategy\ExpressShippingStrategy;
use Modules\Shipping\Domain\Strategy\InternationalShippingStrategy;
use Modules\Shipping\Domain\Strategy\PickupPointShippingStrategy;
use Modules\Shipping\Domain\ValueObject\Shipment;
use Modules\Shipping\Domain\ValueObject\ShippingDestination;
use Shared\Domain\ValueObject\Money;

it('calculates each shipping method with its strategy', function (
    ShippingMethod $method,
    string $countryCode,
    int $weightGrams,
    int $expectedAmount,
): void {
    $calculator = shippingCostCalculator();

    $cost = $calculator->calculate(shipmentFor(
        method: $method,
        countryCode: $countryCode,
        weightGrams: $weightGrams,
    ));

    expect($cost->amount())->toBe($expectedAmount)
        ->and($cost->currency())->toBe('USD');
})->with([
    'courier rounds weight to two kilograms' => [
        ShippingMethod::Courier,
        'US',
        1001,
        800,
    ],
    'express uses its premium rate' => [
        ShippingMethod::Express,
        'US',
        2000,
        1800,
    ],
    'pickup point is free' => [
        ShippingMethod::PickupPoint,
        'US',
        3000,
        0,
    ],
    'international uses its cross-border rate' => [
        ShippingMethod::International,
        'CA',
        1500,
        3700,
    ],
]);

it('rejects a domestic method for an international destination', function (): void {
    $action = fn (): Money => shippingCostCalculator()->calculate(shipmentFor(
        method: ShippingMethod::Courier,
        countryCode: 'CA',
    ));

    expect($action)->toThrow(
        ShippingMethodUnavailable::class,
        'Shipping method "courier" is not available for destination "CA".',
    );
});

it('rejects international shipping for a domestic destination', function (): void {
    $action = fn (): Money => shippingCostCalculator()->calculate(shipmentFor(
        method: ShippingMethod::International,
        countryCode: 'US',
    ));

    expect($action)->toThrow(ShippingMethodUnavailable::class);
});

it('rejects duplicate strategies for one method', function (): void {
    $action = fn (): ShippingStrategyResolver => new ShippingStrategyResolver([
        new CourierShippingStrategy(new ShippingRegionPolicy('US')),
        new CourierShippingStrategy(new ShippingRegionPolicy('US')),
    ]);

    expect($action)->toThrow(DuplicateShippingStrategy::class);
});

it('rejects a method without a registered strategy', function (): void {
    $resolver = new ShippingStrategyResolver([]);

    $action = fn (): IShippingCostStrategy => $resolver->resolve(
        ShippingMethod::Courier,
    );

    expect($action)->toThrow(ShippingStrategyNotFound::class);
});

function shippingCostCalculator(): ShippingCostCalculator
{
    $regions = new ShippingRegionPolicy('US');

    return new ShippingCostCalculator(new ShippingStrategyResolver([
        new CourierShippingStrategy($regions),
        new ExpressShippingStrategy($regions),
        new PickupPointShippingStrategy($regions),
        new InternationalShippingStrategy($regions),
    ]));
}

function shipmentFor(
    ShippingMethod $method,
    string $countryCode,
    int $weightGrams = 1000,
): Shipment {
    return new Shipment(
        method: $method,
        destination: new ShippingDestination($countryCode, '10001'),
        weightGrams: $weightGrams,
        currency: 'USD',
    );
}
