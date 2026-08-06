<?php

declare(strict_types=1);

namespace Modules\Order\Application\Checkout\Rule;

use Modules\Order\Application\Checkout\CheckoutContext;
use Modules\Order\Application\Exception\CheckoutRejected;

final readonly class OrderNotEmptyRule implements ICheckoutRule
{
    public function check(CheckoutContext $context): void
    {
        if ($context->items === []) {
            throw CheckoutRejected::emptyOrder();
        }
    }
}
