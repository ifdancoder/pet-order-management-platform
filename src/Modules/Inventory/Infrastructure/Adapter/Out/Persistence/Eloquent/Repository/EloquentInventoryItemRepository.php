<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Inventory\Application\Exception\SkuAlreadyExists;
use Modules\Inventory\Application\Port\Out\Persistence\IInventoryItemRepository;
use Modules\Inventory\Domain\Entity\InventoryItem;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;
use Modules\Inventory\Domain\ValueObject\Sku;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\InventoryItemMapper;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\InventoryItemModel;

final readonly class EloquentInventoryItemRepository implements IInventoryItemRepository
{
    public function __construct(
        private InventoryItemMapper $mapper,
    ) {}

    public function save(InventoryItem $inventoryItem): void
    {
        $model = InventoryItemModel::query()->find(
            $inventoryItem->id()->value(),
        ) ?? new InventoryItemModel;
        $this->mapper->mapToModel($inventoryItem, $model);

        try {
            $model->save();
        } catch (UniqueConstraintViolationException $exception) {
            throw SkuAlreadyExists::withSku(
                $inventoryItem->sku()->value(),
                $exception,
            );
        }
    }

    public function saveMany(array $inventoryItems): void
    {
        foreach ($inventoryItems as $inventoryItem) {
            $this->save($inventoryItem);
        }
    }

    public function findById(InventoryItemId $inventoryItemId): ?InventoryItem
    {
        $model = InventoryItemModel::query()->find($inventoryItemId->value());

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findBySku(Sku $sku): ?InventoryItem
    {
        $model = InventoryItemModel::query()
            ->where('sku', $sku->value())
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findManyForUpdate(array $inventoryItemIds): array
    {
        $ids = array_map(
            static fn (InventoryItemId $inventoryItemId): string => $inventoryItemId->value(),
            $inventoryItemIds,
        );

        return array_values(InventoryItemModel::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->map($this->mapper->toDomain(...))
            ->all());
    }
}
