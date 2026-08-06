<?php

declare(strict_types=1);

namespace Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository;

use Modules\Promotion\Application\Port\Out\Persistence\IPromotionRepository;
use Modules\Promotion\Domain\Entity\Promotion;
use Modules\Promotion\Domain\ValueObject\PromotionCode;
use Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\PromotionMapper;
use Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PromotionCustomerEligibilityModel;
use Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PromotionModel;
use Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PromotionProductEligibilityModel;

final readonly class EloquentPromotionRepository implements IPromotionRepository
{
    public function __construct(
        private PromotionMapper $mapper,
    ) {}

    public function findByCodes(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        $models = PromotionModel::query()
            ->whereIn('code', array_map(
                static fn (PromotionCode $code): string => $code->value(),
                $codes,
            ))
            ->get();
        $promotionIds = $models->pluck('id')->all();
        $customerEligibilityModels = PromotionCustomerEligibilityModel::query()
            ->whereIn('promotion_id', $promotionIds)
            ->get(['promotion_id', 'customer_id']);
        $productEligibilityModels = PromotionProductEligibilityModel::query()
            ->whereIn('promotion_id', $promotionIds)
            ->get(['promotion_id', 'sku']);
        $customerIds = [];
        $productSkus = [];

        foreach ($customerEligibilityModels as $eligibility) {
            $customerIds[$eligibility->promotion_id][] = $eligibility->customer_id;
        }

        foreach ($productEligibilityModels as $eligibility) {
            $productSkus[$eligibility->promotion_id][] = $eligibility->sku;
        }

        return array_values($models->map(
            fn (PromotionModel $model): Promotion => $this->mapper->toDomain(
                model: $model,
                eligibleCustomerIds: $customerIds[$model->id] ?? [],
                eligibleProductSkus: $productSkus[$model->id] ?? [],
            ),
        )->all());
    }
}
