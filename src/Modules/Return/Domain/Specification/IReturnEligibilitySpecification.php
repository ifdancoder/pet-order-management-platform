<?php

declare(strict_types=1);

namespace Modules\Return\Domain\Specification;

interface IReturnEligibilitySpecification
{
    public function isSatisfiedBy(ReturnEligibilityContext $context): bool;

    public function rejectionReason(): string;
}
