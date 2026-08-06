<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Port\Out\Identity;

use Modules\Inventory\Domain\ValueObject\InventoryItemId;

interface IInventoryItemIdGenerator
{
    public function generate(): InventoryItemId;
}
