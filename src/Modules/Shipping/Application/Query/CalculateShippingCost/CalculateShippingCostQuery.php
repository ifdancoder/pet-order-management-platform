<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Query\CalculateShippingCost;

use Modules\Shipping\Application\Data\ShippingQuote;
use Modules\Shipping\Application\Data\ShippingQuoteRequest;
use Shared\Application\Bus\Query\IQuery;

/** @implements IQuery<ShippingQuote> */
final readonly class CalculateShippingCostQuery implements IQuery
{
    public function __construct(
        public ShippingQuoteRequest $request,
    ) {}
}
