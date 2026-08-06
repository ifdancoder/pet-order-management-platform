<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Customer\Domain\Enum\CustomerStatus;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\CustomerModel;

/** @extends Factory<CustomerModel> */
final class CustomerModelFactory extends Factory
{
    protected $model = CustomerModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => Str::uuid7()->toString(),
            'identity_user_id' => Str::uuid7()->toString(),
            'given_name' => fake()->firstName(),
            'family_name' => fake()->lastName(),
            'phone_number' => null,
            'status' => CustomerStatus::Active->value,
        ];
    }
}
