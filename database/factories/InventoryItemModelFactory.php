<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\InventoryItemModel;

/** @extends Factory<InventoryItemModel> */
final class InventoryItemModelFactory extends Factory
{
    protected $model = InventoryItemModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => Str::uuid7()->toString(),
            'sku' => 'SKU-'.fake()->unique()->numerify('########'),
            'on_hand' => 10,
            'reserved' => 0,
        ];
    }
}
