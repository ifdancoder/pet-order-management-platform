<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\Specification;

final readonly class AndSpecification implements IPromotionSpecification
{
    /** @param list<IPromotionSpecification> $specifications */
    public function __construct(
        private array $specifications,
    ) {}

    public function isSatisfiedBy(PromotionContext $context): bool
    {
        foreach ($this->specifications as $specification) {
            if (! $specification->isSatisfiedBy($context)) {
                return false;
            }
        }

        return true;
    }
}
