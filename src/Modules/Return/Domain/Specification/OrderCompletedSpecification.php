<?php

declare(strict_types=1);

namespace Modules\Return\Domain\Specification;

final readonly class OrderCompletedSpecification implements IReturnEligibilitySpecification
{
    public function isSatisfiedBy(ReturnEligibilityContext $context): bool
    {
        return $context->orderCompleted;
    }

    public function rejectionReason(): string
    {
        return 'Only completed orders can be returned.';
    }
}
