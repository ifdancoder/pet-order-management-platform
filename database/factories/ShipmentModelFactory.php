<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Shipping\Domain\Enum\ShipmentStatus;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ShipmentModel;

/** @extends Factory<ShipmentModel> */
final class ShipmentModelFactory extends Factory
{
    protected $model = ShipmentModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => Str::uuid7()->toString(),
            'order_id' => Str::uuid7()->toString(),
            'method' => ShippingMethod::Courier->value,
            'address' => [
                'recipient_name' => 'Jane Doe',
                'line1' => '100 Main Street',
                'line2' => null,
                'city' => 'New York',
                'region' => 'NY',
                'postal_code' => '10001',
                'country_code' => 'US',
            ],
            'weight_grams' => 1000,
            'cost_amount' => 650,
            'currency' => 'USD',
            'status' => ShipmentStatus::Pending->value,
            'attempts' => 0,
            'available_at' => now(),
        ];
    }
}
