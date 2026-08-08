<?php

declare(strict_types=1);

namespace Modules\Order\Application\Data;

final readonly class PromotionQuote
{
    /** @param list<string> $appliedPromotionCodes */
    public function __construct(
        public array $appliedPromotionCodes,
        public int $discountAmount,
        public string $currency,
    ) {}
}
