<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command\CreateInventoryItem;

use Modules\Inventory\Domain\Entity\InventoryItem;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<InventoryItem> */
final readonly class CreateInventoryItemCommand implements ICommand
{
    public function __construct(
        public string $sku,
        public int $onHand,
    ) {}
}
