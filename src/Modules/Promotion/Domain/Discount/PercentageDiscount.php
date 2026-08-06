<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\Discount;

use Modules\Promotion\Domain\ValueObject\Percentage;
use Shared\Domain\ValueObject\Money;

final readonly class PercentageDiscount implements IDiscountPolicy
{
    public function __construct(
        private Percentage $percentage,
    ) {}

    public function calculate(Money $subtotal): Money
    {
        return new Money(
            amount: intdiv(
                $subtotal->amount() * $this->percentage->basisPoints(),
                10_000,
            ),
            currency: $subtotal->currency(),
        );
    }
}
