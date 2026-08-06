<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\CustomerAddressModel;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\CustomerModel;

/** @extends Factory<CustomerAddressModel> */
final class CustomerAddressModelFactory extends Factory
{
    protected $model = CustomerAddressModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => Str::uuid7()->toString(),
            'customer_id' => CustomerModel::factory(),
            'recipient_name' => fake()->name(),
            'line_1' => fake()->streetAddress(),
            'line_2' => null,
            'city' => fake()->city(),
            'region' => null,
            'postal_code' => fake()->postcode(),
            'country_code' => fake()->countryCode(),
            'is_default' => false,
        ];
    }
}
