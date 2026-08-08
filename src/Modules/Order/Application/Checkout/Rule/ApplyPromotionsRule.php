<?php

declare(strict_types=1);

namespace Modules\Order\Application\Checkout\Rule;

use Modules\Order\Application\Checkout\CheckoutContext;
use Modules\Order\Application\Exception\CheckoutRejected;
use Modules\Order\Application\Port\Out\Checkout\IPromotionCheckoutGateway;

final readonly class ApplyPromotionsRule implements ICheckoutRule
{
    public function __construct(
        private IPromotionCheckoutGateway $promotions,
    ) {}

    public function check(CheckoutContext $context): void
    {
        $quote = $this->promotions->quote($context)
            ?? throw CheckoutRejected::promotionInvalid();

        $context->applyPromotionQuote($quote);
    }
}
