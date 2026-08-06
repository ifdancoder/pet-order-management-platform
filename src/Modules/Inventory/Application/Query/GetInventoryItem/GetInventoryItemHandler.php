<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Query\GetInventoryItem;

use Modules\Inventory\Application\Exception\InventoryItemNotFound;
use Modules\Inventory\Application\Port\Out\Persistence\IInventoryItemRepository;
use Modules\Inventory\Domain\Entity\InventoryItem;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;

final readonly class GetInventoryItemHandler
{
    public function __construct(
        private IInventoryItemRepository $inventoryItems,
    ) {}

    public function __invoke(GetInventoryItemQuery $query): InventoryItem
    {
        return $this->inventoryItems->findById(
            new InventoryItemId($query->inventoryItemId),
        ) ?? throw InventoryItemNotFound::withId($query->inventoryItemId);
    }
}
