<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command\RestockInventoryItem;

use Modules\Inventory\Application\Exception\InventoryItemNotFound;
use Modules\Inventory\Application\Port\Out\Persistence\IInventoryItemRepository;
use Modules\Inventory\Domain\Entity\InventoryItem;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class RestockInventoryItemHandler
{
    public function __construct(
        private IInventoryItemRepository $inventoryItems,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(RestockInventoryItemCommand $command): InventoryItem
    {
        return $this->transaction->run(function () use ($command): InventoryItem {
            $inventoryItemId = new InventoryItemId($command->inventoryItemId);
            $inventoryItem = $this->inventoryItems
                ->findManyForUpdate([$inventoryItemId])[0]
                ?? throw InventoryItemNotFound::withId($command->inventoryItemId);

            $inventoryItem->restock($command->quantity);
            $this->inventoryItems->save($inventoryItem);

            return $inventoryItem;
        });
    }
}
