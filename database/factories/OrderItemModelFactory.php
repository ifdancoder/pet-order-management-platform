<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderItemModel;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderModel;

/** @extends Factory<OrderItemModel> */
final class OrderItemModelFactory extends Factory
{
    protected $model = OrderItemModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => OrderModel::factory(),
            'inventory_item_id' => Str::uuid7()->toString(),
            'sku' => 'SKU-'.fake()->unique()->numerify('########'),
            'quantity' => 1,
            'unit_price_amount' => 1000,
        ];
    }
}
