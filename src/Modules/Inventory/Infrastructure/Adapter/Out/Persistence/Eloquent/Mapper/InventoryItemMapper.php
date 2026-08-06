<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper;

use Modules\Inventory\Domain\Entity\InventoryItem;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;
use Modules\Inventory\Domain\ValueObject\Sku;
use Modules\Inventory\Domain\ValueObject\Stock;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\InventoryItemModel;

final class InventoryItemMapper
{
    public function toDomain(InventoryItemModel $model): InventoryItem
    {
        return new InventoryItem(
            id: new InventoryItemId($model->id),
            sku: new Sku($model->sku),
            stock: new Stock($model->on_hand, $model->reserved),
        );
    }

    public function mapToModel(
        InventoryItem $inventoryItem,
        InventoryItemModel $model,
    ): void {
        $model->id = $inventoryItem->id()->value();
        $model->sku = $inventoryItem->sku()->value();
        $model->on_hand = $inventoryItem->stock()->onHand();
        $model->reserved = $inventoryItem->stock()->reserved();
    }
}
