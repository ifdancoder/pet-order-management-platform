<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Rate;

use Modules\Shipping\Application\Data\ShippingQuote;
use Modules\Shipping\Application\Data\ShippingQuoteRequest;
use Modules\Shipping\Application\Port\In\IShippingRateCalculator;
use Modules\Shipping\Domain\Service\ShippingCostCalculator;
use Modules\Shipping\Domain\ValueObject\Shipment;
use Modules\Shipping\Domain\ValueObject\ShippingDestination;

final readonly class ShippingRateCalculator implements IShippingRateCalculator
{
    public function __construct(
        private ShippingCostCalculator $costs,
    ) {}

    public function quote(ShippingQuoteRequest $request): ShippingQuote
    {
        $cost = $this->costs->calculate(new Shipment(
            method: $request->method,
            destination: new ShippingDestination(
                $request->countryCode,
                $request->postalCode,
            ),
            weightGrams: $request->weightGrams,
            currency: $request->currency,
        ));

        return new ShippingQuote(
            method: $request->method,
            amount: $cost->amount(),
            currency: $cost->currency(),
        );
    }
}
