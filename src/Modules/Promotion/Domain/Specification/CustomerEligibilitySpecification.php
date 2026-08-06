<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\Specification;

final readonly class CustomerEligibilitySpecification implements IPromotionSpecification
{
    /** @param list<string> $eligibleCustomerIds */
    public function __construct(
        private array $eligibleCustomerIds,
    ) {}

    public function isSatisfiedBy(PromotionContext $context): bool
    {
        return $this->eligibleCustomerIds === []
            || in_array($context->customerId, $this->eligibleCustomerIds, true);
    }
}
