<?php

declare(strict_types=1);

namespace Modules\Order\Application\Data;

use Modules\Order\Domain\Entity\OrderItem;
use Modules\Order\Domain\ValueObject\InventoryItemId;
use Modules\Order\Domain\ValueObject\Sku;
use Shared\Domain\ValueObject\Money;

final readonly class OrderItemData
{
    public function __construct(
        public string $inventoryItemId,
        public string $sku,
        public int $quantity,
        public int $unitPriceAmount,
    ) {}

    public function toOrderItem(string $currency): OrderItem
    {
        return new OrderItem(
            inventoryItemId: new InventoryItemId($this->inventoryItemId),
            sku: new Sku($this->sku),
            quantity: $this->quantity,
            unitPrice: new Money($this->unitPriceAmount, $currency),
        );
    }
}
