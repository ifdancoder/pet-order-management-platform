<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\InventoryItemModel;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReservationLineModel;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReservationModel;

/** @extends Factory<ReservationLineModel> */
final class ReservationLineModelFactory extends Factory
{
    protected $model = ReservationLineModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'reservation_id' => ReservationModel::factory(),
            'inventory_item_id' => InventoryItemModel::factory(),
            'quantity' => 1,
        ];
    }
}
