<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Entity;

use InvalidArgumentException;
use Modules\Order\Domain\ValueObject\InventoryItemId;
use Modules\Order\Domain\ValueObject\Sku;
use Shared\Domain\ValueObject\Money;

final readonly class OrderItem
{
    public function __construct(
        private InventoryItemId $inventoryItemId,
        private Sku $sku,
        private int $quantity,
        private Money $unitPrice,
    ) {
        if ($quantity < 1) {
            throw new InvalidArgumentException(
                'Order item quantity must be positive.',
            );
        }
    }

    public function inventoryItemId(): InventoryItemId
    {
        return $this->inventoryItemId;
    }

    public function sku(): Sku
    {
        return $this->sku;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function subtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}
