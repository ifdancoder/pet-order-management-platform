<?php

declare(strict_types=1);

namespace Modules\Return\Domain\Specification;

use DateTimeImmutable;
use Modules\Return\Domain\ValueObject\ReturnItem;

final readonly class ReturnEligibilityContext
{
    /**
     * @param  list<ReturnItem>  $requestedItems
     * @param  array<string, int>  $purchasedQuantities
     */
    public function __construct(
        public bool $orderCompleted,
        public DateTimeImmutable $completedAt,
        public DateTimeImmutable $requestedAt,
        public array $requestedItems,
        public array $purchasedQuantities,
    ) {}
}
