<?php

declare(strict_types=1);

namespace Modules\Order\Application\Checkout\Rule;

use Modules\Order\Application\Checkout\CheckoutContext;

interface ICheckoutRule
{
    public function check(CheckoutContext $context): void;
}
