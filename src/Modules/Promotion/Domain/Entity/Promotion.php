<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\Entity;

use Modules\Promotion\Domain\Discount\IDiscountPolicy;
use Modules\Promotion\Domain\Specification\IPromotionSpecification;
use Modules\Promotion\Domain\Specification\PromotionContext;
use Modules\Promotion\Domain\ValueObject\PromotionCode;
use Modules\Promotion\Domain\ValueObject\PromotionId;

final readonly class Promotion
{
    public function __construct(
        private PromotionId $id,
        private PromotionCode $code,
        private bool $enabled,
        private IPromotionSpecification $eligibility,
        private IDiscountPolicy $discount,
    ) {}

    public function id(): PromotionId
    {
        return $this->id;
    }

    public function code(): PromotionCode
    {
        return $this->code;
    }

    public function isApplicable(PromotionContext $context): bool
    {
        return $this->enabled && $this->eligibility->isSatisfiedBy($context);
    }

    public function discount(): IDiscountPolicy
    {
        return $this->discount;
    }
}
