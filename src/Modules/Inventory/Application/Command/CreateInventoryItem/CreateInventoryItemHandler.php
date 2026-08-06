<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command\CreateInventoryItem;

use Modules\Inventory\Application\Exception\SkuAlreadyExists;
use Modules\Inventory\Application\Port\Out\Identity\IInventoryItemIdGenerator;
use Modules\Inventory\Application\Port\Out\Persistence\IInventoryItemRepository;
use Modules\Inventory\Domain\Entity\InventoryItem;
use Modules\Inventory\Domain\ValueObject\Sku;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class CreateInventoryItemHandler
{
    public function __construct(
        private IInventoryItemRepository $inventoryItems,
        private IInventoryItemIdGenerator $inventoryItemIds,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(CreateInventoryItemCommand $command): InventoryItem
    {
        return $this->transaction->run(function () use ($command): InventoryItem {
            $sku = new Sku($command->sku);

            if ($this->inventoryItems->findBySku($sku) !== null) {
                throw SkuAlreadyExists::withSku($sku->value());
            }

            $inventoryItem = InventoryItem::create(
                $this->inventoryItemIds->generate(),
                $sku,
                $command->onHand,
            );
            $this->inventoryItems->save($inventoryItem);

            return $inventoryItem;
        });
    }
}
