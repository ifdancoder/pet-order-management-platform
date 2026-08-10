<?php

declare(strict_types=1);

namespace Modules\Return\Domain\Specification;

use InvalidArgumentException;

final readonly class WithinReturnWindowSpecification implements IReturnEligibilitySpecification
{
    public function __construct(
        private int $windowDays,
    ) {
        if ($windowDays < 1) {
            throw new InvalidArgumentException('Return window must be positive.');
        }
    }

    public function isSatisfiedBy(ReturnEligibilityContext $context): bool
    {
        $deadline = $context->completedAt->modify(sprintf('+%d days', $this->windowDays));

        return $context->requestedAt >= $context->completedAt
            && $context->requestedAt <= $deadline;
    }

    public function rejectionReason(): string
    {
        return 'The return window has expired.';
    }
}
