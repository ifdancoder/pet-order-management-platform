<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\Discount;

use Shared\Domain\ValueObject\Money;

final readonly class FixedDiscount implements IDiscountPolicy
{
    public function __construct(
        private Money $amount,
    ) {}

    public function calculate(Money $subtotal): Money
    {
        return $this->amount->minimum($subtotal);
    }
}
