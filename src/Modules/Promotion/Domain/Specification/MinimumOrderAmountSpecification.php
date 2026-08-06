<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\Specification;

use Shared\Domain\ValueObject\Money;

final readonly class MinimumOrderAmountSpecification implements IPromotionSpecification
{
    public function __construct(
        private Money $minimum,
    ) {}

    public function isSatisfiedBy(PromotionContext $context): bool
    {
        return $context->subtotal->minimum($this->minimum)->equals($this->minimum);
    }
}
