<?php

declare(strict_types=1);

use Modules\Shipping\Application\Data\ShippingQuoteRequest;
use Modules\Shipping\Application\Query\CalculateShippingCost\CalculateShippingCostQuery;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Shared\Application\Bus\Query\IQueryBus;

it('calculates a configured shipping rate through the query bus', function (): void {
    config()->set('shipping.domestic_country_code', 'US');

    $quote = app(IQueryBus::class)->ask(new CalculateShippingCostQuery(
        new ShippingQuoteRequest(
            method: ShippingMethod::Express,
            countryCode: 'US',
            postalCode: '10001',
            weightGrams: 1250,
            currency: 'USD',
        ),
    ));

    expect($quote->method)->toBe(ShippingMethod::Express)
        ->and($quote->amount)->toBe(1800)
        ->and($quote->currency)->toBe('USD');
});
