<?php

declare(strict_types=1);

namespace Modules\Order\Application\Port\Out\Checkout;

use Modules\Order\Application\Data\ShippingDetailsData;
use Modules\Order\Application\Data\ShippingQuote;

interface IShippingCheckoutGateway
{
    public function quote(
        ShippingDetailsData $shipping,
        string $currency,
    ): ShippingQuote;

    public function createShipment(
        string $orderId,
        ShippingDetailsData $shipping,
        ShippingQuote $quote,
    ): void;
}
