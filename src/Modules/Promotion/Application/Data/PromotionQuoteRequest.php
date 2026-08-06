<?php

declare(strict_types=1);

namespace Modules\Promotion\Application\Data;

use DateTimeImmutable;

final readonly class PromotionQuoteRequest
{
    /**
     * @param  list<string>  $promotionCodes
     * @param  list<string>  $productSkus
     */
    public function __construct(
        public array $promotionCodes,
        public string $customerId,
        public int $subtotalAmount,
        public string $currency,
        public array $productSkus,
        public DateTimeImmutable $evaluatedAt,
    ) {}
}
