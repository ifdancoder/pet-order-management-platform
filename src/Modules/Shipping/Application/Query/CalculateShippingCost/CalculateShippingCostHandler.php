<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Query\CalculateShippingCost;

use Modules\Shipping\Application\Data\ShippingQuote;
use Modules\Shipping\Application\Port\In\IShippingRateCalculator;

final readonly class CalculateShippingCostHandler
{
    public function __construct(
        private IShippingRateCalculator $rates,
    ) {}

    public function __invoke(CalculateShippingCostQuery $query): ShippingQuote
    {
        return $this->rates->quote($query->request);
    }
}
