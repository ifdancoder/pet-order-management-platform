<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Promotion\Domain\Enum\DiscountType;
use Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PromotionModel;

/** @extends Factory<PromotionModel> */
final class PromotionModelFactory extends Factory
{
    protected $model = PromotionModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => Str::uuid7()->toString(),
            'code' => 'PROMO-'.fake()->unique()->numerify('########'),
            'enabled' => true,
            'discount_type' => DiscountType::Percentage->value,
            'discount_value' => 1_000,
            'currency' => 'USD',
            'minimum_order_amount' => null,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ];
    }
}
