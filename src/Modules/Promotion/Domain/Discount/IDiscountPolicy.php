<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\Discount;

use Shared\Domain\ValueObject\Money;

interface IDiscountPolicy
{
    public function calculate(Money $subtotal): Money;
}
