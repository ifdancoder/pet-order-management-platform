<?php

declare(strict_types=1);

namespace Modules\Return\Domain\Service;

use Modules\Return\Domain\Exception\ReturnNotEligible;
use Modules\Return\Domain\Specification\AndReturnEligibilitySpecification;
use Modules\Return\Domain\Specification\ReturnEligibilityContext;

final readonly class ReturnEligibilityPolicy
{
    public function __construct(
        private AndReturnEligibilitySpecification $specification,
    ) {}

    public function assertEligible(ReturnEligibilityContext $context): void
    {
        $failure = $this->specification->firstFailure($context);

        if ($failure !== null) {
            throw ReturnNotEligible::because($failure);
        }
    }
}
