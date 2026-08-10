<?php

declare(strict_types=1);

namespace Modules\Return\Domain\ValueObject;

use InvalidArgumentException;
use Shared\Domain\ValueObject\Money;

final readonly class ReturnItem
{
    public function __construct(
        private string $inventoryItemId,
        private int $quantity,
        private Money $unitPrice,
    ) {
        if (trim($inventoryItemId) === '') {
            throw new InvalidArgumentException('Inventory item ID cannot be blank.');
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('Return quantity must be positive.');
        }
    }

    public function inventoryItemId(): string
    {
        return $this->inventoryItemId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function refundAmount(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}
