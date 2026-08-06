<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\Specification;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class DateRangeSpecification implements IPromotionSpecification
{
    public function __construct(
        private DateTimeImmutable $startsAt,
        private DateTimeImmutable $endsAt,
    ) {
        if ($endsAt < $startsAt) {
            throw new InvalidArgumentException(
                'Promotion end date must not precede its start date.',
            );
        }
    }

    public function isSatisfiedBy(PromotionContext $context): bool
    {
        return $context->evaluatedAt >= $this->startsAt
            && $context->evaluatedAt <= $this->endsAt;
    }
}
