<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Strategy;

use Modules\Shipping\Domain\Contract\IShippingCostStrategy;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Domain\Service\ShippingRegionPolicy;
use Modules\Shipping\Domain\ValueObject\Shipment;
use Shared\Domain\ValueObject\Money;

final readonly class InternationalShippingStrategy implements IShippingCostStrategy
{
    public function __construct(
        private ShippingRegionPolicy $regions,
    ) {}

    public function method(): ShippingMethod
    {
        return ShippingMethod::International;
    }

    public function calculate(Shipment $shipment): Money
    {
        $this->regions->requireInternational($shipment);

        return new Money(
            2500 + ($shipment->billableKilograms() * 600),
            $shipment->currency(),
        );
    }
}
