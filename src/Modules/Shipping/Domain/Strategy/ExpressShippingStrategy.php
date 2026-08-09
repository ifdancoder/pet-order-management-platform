<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Strategy;

use Modules\Shipping\Domain\Contract\IShippingCostStrategy;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Domain\Service\ShippingRegionPolicy;
use Modules\Shipping\Domain\ValueObject\Shipment;
use Shared\Domain\ValueObject\Money;

final readonly class ExpressShippingStrategy implements IShippingCostStrategy
{
    public function __construct(
        private ShippingRegionPolicy $regions,
    ) {}

    public function method(): ShippingMethod
    {
        return ShippingMethod::Express;
    }

    public function calculate(Shipment $shipment): Money
    {
        $this->regions->requireDomestic($shipment);

        return new Money(
            1200 + ($shipment->billableKilograms() * 300),
            $shipment->currency(),
        );
    }
}
