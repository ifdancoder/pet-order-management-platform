<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Adapter\Out\Identity;

use Illuminate\Support\Str;
use Modules\Inventory\Application\Port\Out\Identity\IInventoryItemIdGenerator;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;

final class LaravelInventoryItemIdGenerator implements IInventoryItemIdGenerator
{
    public function generate(): InventoryItemId
    {
        return new InventoryItemId(Str::uuid7()->toString());
    }
}
