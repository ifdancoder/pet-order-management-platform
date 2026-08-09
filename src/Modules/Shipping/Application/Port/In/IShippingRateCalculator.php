<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Port\In;

use Modules\Shipping\Application\Data\ShippingQuote;
use Modules\Shipping\Application\Data\ShippingQuoteRequest;

interface IShippingRateCalculator
{
    public function quote(ShippingQuoteRequest $request): ShippingQuote;
}
