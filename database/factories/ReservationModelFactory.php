<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Inventory\Domain\Enum\ReservationStatus;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReservationModel;

/** @extends Factory<ReservationModel> */
final class ReservationModelFactory extends Factory
{
    protected $model = ReservationModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => Str::uuid7()->toString(),
            'reservation_key' => Str::uuid()->toString(),
            'status' => ReservationStatus::Active->value,
        ];
    }
}
