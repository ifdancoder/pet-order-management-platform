<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\Specification;

interface IPromotionSpecification
{
    public function isSatisfiedBy(PromotionContext $context): bool;
}
