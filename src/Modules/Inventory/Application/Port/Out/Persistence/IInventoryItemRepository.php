<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Port\Out\Persistence;

use Modules\Inventory\Domain\Entity\InventoryItem;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;
use Modules\Inventory\Domain\ValueObject\Sku;

interface IInventoryItemRepository
{
    public function save(InventoryItem $inventoryItem): void;

    /** @param list<InventoryItem> $inventoryItems */
    public function saveMany(array $inventoryItems): void;

    public function findById(InventoryItemId $inventoryItemId): ?InventoryItem;

    public function findBySku(Sku $sku): ?InventoryItem;

    /**
     * Returned items must be locked in ascending ID order until the current
     * transaction ends.
     *
     * @param  list<InventoryItemId>  $inventoryItemIds
     * @return list<InventoryItem>
     */
    public function findManyForUpdate(array $inventoryItemIds): array;
}
