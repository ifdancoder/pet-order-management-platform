<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\Specification;

use DateTimeImmutable;
use Shared\Domain\ValueObject\Money;

final readonly class PromotionContext
{
    /** @param list<string> $productSkus */
    public function __construct(
        public string $customerId,
        public Money $subtotal,
        public array $productSkus,
        public DateTimeImmutable $evaluatedAt,
    ) {}
}
