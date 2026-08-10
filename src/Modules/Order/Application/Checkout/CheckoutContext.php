<?php

declare(strict_types=1);

namespace Modules\Order\Application\Checkout;

use Modules\Order\Application\Data\OrderItemData;
use Modules\Order\Application\Data\PromotionQuote;
use Modules\Order\Application\Data\ShippingDetailsData;
use Modules\Order\Application\Data\ShippingQuote;

final class CheckoutContext
{
    private PromotionQuote $promotionQuote;

    private ?ShippingQuote $shippingQuote = null;

    /**
     * @param  list<OrderItemData>  $items
     * @param  list<string>  $promotionCodes
     */
    public function __construct(
        public readonly string $customerId,
        public readonly string $currency,
        public readonly array $items,
        public readonly array $promotionCodes = [],
        public readonly ?ShippingDetailsData $shipping = null,
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

    public function applyShippingQuote(ShippingQuote $quote): void
    {
        $this->shippingQuote = $quote;
    }

    public function shippingQuote(): ?ShippingQuote
    {
        return $this->shippingQuote;
    }
}
