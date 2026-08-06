<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command\RestockInventoryItem;

use Modules\Inventory\Domain\Entity\InventoryItem;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<InventoryItem> */
final readonly class RestockInventoryItemCommand implements ICommand
{
    public function __construct(
        public string $inventoryItemId,
        public int $quantity,
    ) {}
}
