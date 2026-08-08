<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderModel;

/** @extends Factory<OrderModel> */
final class OrderModelFactory extends Factory
{
    protected $model = OrderModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => Str::uuid7()->toString(),
            'customer_id' => Str::uuid7()->toString(),
            'currency' => 'USD',
            'status' => OrderStatus::Draft->value,
            'discount_amount' => 0,
            'total_amount' => 0,
        ];
    }
}
