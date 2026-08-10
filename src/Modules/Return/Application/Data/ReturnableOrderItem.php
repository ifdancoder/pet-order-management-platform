<?php

declare(strict_types=1);

namespace Modules\Return\Application\Data;

final readonly class ReturnableOrderItem
{
    public function __construct(
        public string $inventoryItemId,
        public int $quantity,
        public int $unitPriceAmount,
    ) {}
}
