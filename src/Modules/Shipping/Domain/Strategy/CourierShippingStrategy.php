<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Strategy;

use Modules\Shipping\Domain\Contract\IShippingCostStrategy;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Domain\Service\ShippingRegionPolicy;
use Modules\Shipping\Domain\ValueObject\ShippingRateRequest;
use Shared\Domain\ValueObject\Money;

final readonly class CourierShippingStrategy implements IShippingCostStrategy
{
    public function __construct(
        private ShippingRegionPolicy $regions,
    ) {}

    public function method(): ShippingMethod
    {
        return ShippingMethod::Courier;
    }

    public function calculate(ShippingRateRequest $shipment): Money
    {
        $this->regions->requireDomestic($shipment);

        return new Money(
            500 + ($shipment->billableKilograms() * 150),
            $shipment->currency(),
        );
    }
}
