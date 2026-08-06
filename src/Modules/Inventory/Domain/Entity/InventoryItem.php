<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entity;

use Modules\Inventory\Domain\ValueObject\InventoryItemId;
use Modules\Inventory\Domain\ValueObject\Sku;
use Modules\Inventory\Domain\ValueObject\Stock;

final class InventoryItem
{
    public function __construct(
        private readonly InventoryItemId $id,
        private readonly Sku $sku,
        private Stock $stock,
    ) {}

    public static function create(
        InventoryItemId $id,
        Sku $sku,
        int $onHand,
    ): self {
        return new self($id, $sku, Stock::fromOnHand($onHand));
    }

    public function id(): InventoryItemId
    {
        return $this->id;
    }

    public function sku(): Sku
    {
        return $this->sku;
    }

    public function stock(): Stock
    {
        return $this->stock;
    }

    public function reserve(int $quantity): void
    {
        $this->stock = $this->stock->reserve($this->id, $quantity);
    }

    public function release(int $quantity): void
    {
        $this->stock = $this->stock->release($quantity);
    }

    public function restock(int $quantity): void
    {
        $this->stock = $this->stock->restock($quantity);
    }
}
