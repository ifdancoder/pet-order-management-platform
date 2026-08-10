<?php

declare(strict_types=1);

namespace Modules\Order\Application\Checkout\Rule;

use Modules\Order\Application\Checkout\CheckoutContext;
use Modules\Order\Application\Port\Out\Checkout\IShippingCheckoutGateway;

final readonly class CalculateShippingRule implements ICheckoutRule
{
    public function __construct(
        private IShippingCheckoutGateway $shipping,
    ) {}

    public function check(CheckoutContext $context): void
    {
        if ($context->shipping === null) {
            return;
        }

        $context->applyShippingQuote($this->shipping->quote(
            $context->shipping,
            $context->currency,
        ));
    }
}
