<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Contract;

use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Domain\ValueObject\Shipment;
use Shared\Domain\ValueObject\Money;

interface IShippingCostStrategy
{
    public function method(): ShippingMethod;

    public function calculate(Shipment $shipment): Money;
}
