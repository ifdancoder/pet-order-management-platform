<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Service;

use Modules\Shipping\Domain\ValueObject\ShippingRateRequest;
use Shared\Domain\ValueObject\Money;

final readonly class ShippingCostCalculator
{
    public function __construct(
        private ShippingStrategyResolver $strategies,
    ) {}

    public function calculate(ShippingRateRequest $shipment): Money
    {
        return $this->strategies
            ->resolve($shipment->method())
            ->calculate($shipment);
    }
}
