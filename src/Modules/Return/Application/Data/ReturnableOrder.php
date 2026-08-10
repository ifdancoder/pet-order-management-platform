<?php

declare(strict_types=1);

namespace Modules\Return\Application\Data;

use DateTimeImmutable;

final readonly class ReturnableOrder
{
    /** @param list<ReturnableOrderItem> $items */
    public function __construct(
        public string $orderId,
        public string $customerId,
        public bool $completed,
        public string $currency,
        public DateTimeImmutable $completedAt,
        public array $items,
    ) {}
}
