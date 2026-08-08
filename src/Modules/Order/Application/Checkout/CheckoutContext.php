<?php

declare(strict_types=1);

namespace Modules\Order\Application\Checkout;

use Modules\Order\Application\Data\OrderItemData;
use Modules\Order\Application\Data\PromotionQuote;

final class CheckoutContext
{
    private PromotionQuote $promotionQuote;

    /**
     * @param  list<OrderItemData>  $items
     * @param  list<string>  $promotionCodes
     */
    public function __construct(
        public readonly string $customerId,
        public readonly string $currency,
        public readonly array $items,
        public readonly array $promotionCodes = [],
    ) {
        $this->promotionQuote = new PromotionQuote([], 0, $currency);
    }

    public function applyPromotionQuote(PromotionQuote $quote): void
    {
        $this->promotionQuote = $quote;
    }

    public function promotionQuote(): PromotionQuote
    {
        return $this->promotionQuote;
    }
}
