<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Query\GetInventoryItem;

use Modules\Inventory\Domain\Entity\InventoryItem;
use Shared\Application\Bus\Query\IQuery;

/** @implements IQuery<InventoryItem> */
final readonly class GetInventoryItemQuery implements IQuery
{
    public function __construct(
        public string $inventoryItemId,
    ) {}
}
