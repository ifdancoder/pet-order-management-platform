<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Checkout;

use InvalidArgumentException;
use Modules\Order\Application\Checkout\CheckoutContext;
use Modules\Order\Application\Data\OrderItemData;
use Modules\Order\Application\Data\PromotionQuote;
use Modules\Order\Application\Port\Out\Checkout\IPromotionCheckoutGateway;
use Modules\Promotion\Application\Data\PromotionQuoteRequest;
use Modules\Promotion\Application\Exception\DuplicatePromotionCode;
use Modules\Promotion\Application\Exception\PromotionNotApplicable;
use Modules\Promotion\Application\Exception\PromotionNotFound;
use Modules\Promotion\Application\Port\In\IPromotionCheckout;
use Shared\Domain\ValueObject\Money;

final readonly class PromotionCheckoutGateway implements IPromotionCheckoutGateway
{
    public function __construct(
        private IPromotionCheckout $promotions,
    ) {}

    public function quote(CheckoutContext $context): ?PromotionQuote
    {
        try {
            $quote = $this->promotions->quote(new PromotionQuoteRequest(
                promotionCodes: $context->promotionCodes,
                customerId: $context->customerId,
                subtotalAmount: $this->subtotal($context),
                currency: $context->currency,
                productSkus: array_map(
                    static fn (OrderItemData $item): string => $item->sku,
                    $context->items,
                ),
                evaluatedAt: now()->toDateTimeImmutable(),
            ));
        } catch (
            DuplicatePromotionCode|
            InvalidArgumentException|
            PromotionNotApplicable|
            PromotionNotFound
        ) {
            return null;
        }

        return new PromotionQuote(
            appliedPromotionCodes: $quote->appliedPromotionCodes,
            discountAmount: $quote->discountAmount,
            currency: $quote->currency,
        );
    }

    private function subtotal(CheckoutContext $context): int
    {
        $subtotal = Money::zero($context->currency);

        foreach ($context->items as $item) {
            $subtotal = $subtotal->add(
                new Money($item->unitPriceAmount, $context->currency)
                    ->multiply($item->quantity),
            );
        }

        return $subtotal->amount();
    }
}
