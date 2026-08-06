<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\Specification;

final readonly class ProductEligibilitySpecification implements IPromotionSpecification
{
    /** @param list<string> $eligibleProductSkus */
    public function __construct(
        private array $eligibleProductSkus,
    ) {}

    public function isSatisfiedBy(PromotionContext $context): bool
    {
        if ($this->eligibleProductSkus === []) {
            return true;
        }

        return array_intersect(
            $context->productSkus,
            $this->eligibleProductSkus,
        ) !== [];
    }
}
