<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\Discount;

use Shared\Domain\ValueObject\Money;

final readonly class CompositeDiscount implements IDiscountPolicy
{
    /** @param list<IDiscountPolicy> $discounts */
    public function __construct(
        private array $discounts,
    ) {}

    public function calculate(Money $subtotal): Money
    {
        $totalDiscount = Money::zero($subtotal->currency());
        $remaining = $subtotal;

        foreach ($this->discounts as $discount) {
            $currentDiscount = $discount->calculate($remaining);
            $totalDiscount = $totalDiscount->add($currentDiscount);
            $remaining = $remaining->subtract($currentDiscount);
        }

        return $totalDiscount;
    }
}
