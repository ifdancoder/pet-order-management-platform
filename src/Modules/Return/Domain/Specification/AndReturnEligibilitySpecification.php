<?php

declare(strict_types=1);

namespace Modules\Return\Domain\Specification;

use LogicException;

final readonly class AndReturnEligibilitySpecification implements IReturnEligibilitySpecification
{
    /** @var list<IReturnEligibilitySpecification> */
    private array $specifications;

    /** @param iterable<IReturnEligibilitySpecification> $specifications */
    public function __construct(iterable $specifications)
    {
        $this->specifications = array_values(
            is_array($specifications)
                ? $specifications
                : iterator_to_array($specifications),
        );

        if ($this->specifications === []) {
            throw new LogicException('At least one return specification is required.');
        }
    }

    public function isSatisfiedBy(ReturnEligibilityContext $context): bool
    {
        return $this->firstFailure($context) === null;
    }

    public function rejectionReason(): string
    {
        return 'Return eligibility requirements were not met.';
    }

    public function firstFailure(ReturnEligibilityContext $context): ?string
    {
        foreach ($this->specifications as $specification) {
            if (! $specification->isSatisfiedBy($context)) {
                return $specification->rejectionReason();
            }
        }

        return null;
    }
}
