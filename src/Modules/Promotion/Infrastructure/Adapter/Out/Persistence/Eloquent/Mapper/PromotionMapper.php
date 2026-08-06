<?php

declare(strict_types=1);

namespace Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper;

use Modules\Promotion\Domain\Discount\FixedDiscount;
use Modules\Promotion\Domain\Discount\IDiscountPolicy;
use Modules\Promotion\Domain\Discount\PercentageDiscount;
use Modules\Promotion\Domain\Entity\Promotion;
use Modules\Promotion\Domain\Enum\DiscountType;
use Modules\Promotion\Domain\Specification\AndSpecification;
use Modules\Promotion\Domain\Specification\CustomerEligibilitySpecification;
use Modules\Promotion\Domain\Specification\DateRangeSpecification;
use Modules\Promotion\Domain\Specification\MinimumOrderAmountSpecification;
use Modules\Promotion\Domain\Specification\ProductEligibilitySpecification;
use Modules\Promotion\Domain\ValueObject\Percentage;
use Modules\Promotion\Domain\ValueObject\PromotionCode;
use Modules\Promotion\Domain\ValueObject\PromotionId;
use Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PromotionModel;
use Shared\Domain\ValueObject\Money;

final readonly class PromotionMapper
{
    /**
     * @param  list<string>  $eligibleCustomerIds
     * @param  list<string>  $eligibleProductSkus
     */
    public function toDomain(
        PromotionModel $model,
        array $eligibleCustomerIds,
        array $eligibleProductSkus,
    ): Promotion {
        $specifications = [
            new DateRangeSpecification(
                $model->startsAt(),
                $model->endsAt(),
            ),
            new CustomerEligibilitySpecification($eligibleCustomerIds),
            new ProductEligibilitySpecification($eligibleProductSkus),
        ];

        if ($model->minimum_order_amount !== null) {
            $specifications[] = new MinimumOrderAmountSpecification(
                new Money($model->minimum_order_amount, $model->currency),
            );
        }

        return new Promotion(
            id: new PromotionId($model->id),
            code: new PromotionCode($model->code),
            enabled: $model->enabled,
            eligibility: new AndSpecification($specifications),
            discount: $this->discount($model),
        );
    }

    private function discount(PromotionModel $model): IDiscountPolicy
    {
        return match (DiscountType::from($model->discount_type)) {
            DiscountType::Percentage => new PercentageDiscount(
                new Percentage($model->discount_value),
            ),
            DiscountType::Fixed => new FixedDiscount(
                new Money($model->discount_value, $model->currency),
            ),
        };
    }
}
