<?php

declare(strict_types=1);

namespace Modules\Order\Application\Port\Out\Checkout;

use Modules\Order\Application\Checkout\CheckoutContext;
use Modules\Order\Application\Data\PromotionQuote;

interface IPromotionCheckoutGateway
{
    public function quote(CheckoutContext $context): ?PromotionQuote;
}
