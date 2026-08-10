<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Return\Domain\Enum\ReturnStatus;
use Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReturnRequestModel;

/** @extends Factory<ReturnRequestModel> */
final class ReturnRequestModelFactory extends Factory
{
    protected $model = ReturnRequestModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => Str::uuid7()->toString(),
            'order_id' => Str::uuid7()->toString(),
            'customer_id' => Str::uuid7()->toString(),
            'status' => ReturnStatus::Requested->value,
            'reason' => fake()->sentence(),
            'currency' => 'USD',
            'refund_amount' => 1000,
            'requested_at' => now(),
        ];
    }
}
