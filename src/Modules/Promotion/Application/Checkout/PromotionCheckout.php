<?php

declare(strict_types=1);

namespace Modules\Promotion\Application\Checkout;

use Modules\Promotion\Application\Data\PromotionQuote;
use Modules\Promotion\Application\Data\PromotionQuoteRequest;
use Modules\Promotion\Application\Exception\DuplicatePromotionCode;
use Modules\Promotion\Application\Exception\PromotionNotApplicable;
use Modules\Promotion\Application\Exception\PromotionNotFound;
use Modules\Promotion\Application\Port\In\IPromotionCheckout;
use Modules\Promotion\Application\Port\Out\Persistence\IPromotionRepository;
use Modules\Promotion\Domain\Discount\CompositeDiscount;
use Modules\Promotion\Domain\Discount\IDiscountPolicy;
use Modules\Promotion\Domain\Entity\Promotion;
use Modules\Promotion\Domain\Specification\PromotionContext;
use Modules\Promotion\Domain\ValueObject\PromotionCode;
use Shared\Domain\ValueObject\Money;

final readonly class PromotionCheckout implements IPromotionCheckout
{
    public function __construct(
        private IPromotionRepository $promotions,
    ) {}

    public function quote(PromotionQuoteRequest $request): PromotionQuote
    {
        $codes = $this->codes($request->promotionCodes);

        if ($codes === []) {
            return new PromotionQuote([], 0, $request->currency);
        }

        $promotionsByCode = [];

        foreach ($this->promotions->findByCodes($codes) as $promotion) {
            $promotionsByCode[$promotion->code()->value()] = $promotion;
        }

        $context = new PromotionContext(
            customerId: $request->customerId,
            subtotal: new Money($request->subtotalAmount, $request->currency),
            productSkus: $request->productSkus,
            evaluatedAt: $request->evaluatedAt,
        );
        $orderedPromotions = [];

        foreach ($codes as $code) {
            $promotion = $promotionsByCode[$code->value()]
                ?? throw PromotionNotFound::withCode($code->value());

            if (! $promotion->isApplicable($context)) {
                throw PromotionNotApplicable::withCode($code->value());
            }

            $orderedPromotions[] = $promotion;
        }

        $discount = (new CompositeDiscount(array_map(
            static fn (Promotion $promotion): IDiscountPolicy => $promotion->discount(),
            $orderedPromotions,
        )))->calculate($context->subtotal);

        return new PromotionQuote(
            appliedPromotionCodes: array_map(
                static fn (PromotionCode $code): string => $code->value(),
                $codes,
            ),
            discountAmount: $discount->amount(),
            currency: $discount->currency(),
        );
    }

    /**
     * @param  list<string>  $rawCodes
     * @return list<PromotionCode>
     */
    private function codes(array $rawCodes): array
    {
        $codes = [];
        $seen = [];

        foreach ($rawCodes as $rawCode) {
            $code = new PromotionCode($rawCode);

            if (isset($seen[$code->value()])) {
                throw DuplicatePromotionCode::withCode($code->value());
            }

            $seen[$code->value()] = true;
            $codes[] = $code;
        }

        return $codes;
    }
}
