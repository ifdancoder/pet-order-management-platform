<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObject;

use InvalidArgumentException;
use Modules\Inventory\Domain\Exception\InsufficientStock;
use Modules\Inventory\Domain\Exception\InvalidStockRelease;

final readonly class Stock
{
    /** @var int<0, max> */
    private int $onHand;

    /** @var int<0, max> */
    private int $reserved;

    public function __construct(int $onHand, int $reserved)
    {
        if ($onHand < 0) {
            throw new InvalidArgumentException('On-hand stock cannot be negative.');
        }

        if ($reserved < 0 || $reserved > $onHand) {
            throw new InvalidArgumentException(
                'Reserved stock must be between zero and on-hand stock.',
            );
        }

        $this->onHand = $onHand;
        $this->reserved = $reserved;
    }

    public static function fromOnHand(int $onHand): self
    {
        return new self($onHand, 0);
    }

    /** @return int<0, max> */
    public function onHand(): int
    {
        return $this->onHand;
    }

    /** @return int<0, max> */
    public function reserved(): int
    {
        return $this->reserved;
    }

    public function available(): int
    {
        return $this->onHand - $this->reserved;
    }

    public function reserve(
        InventoryItemId $inventoryItemId,
        int $quantity,
    ): self {
        $this->assertPositiveQuantity($quantity);

        if ($quantity > $this->available()) {
            throw InsufficientStock::forItem(
                $inventoryItemId,
                $quantity,
                $this->available(),
            );
        }

        return new self($this->onHand, $this->reserved + $quantity);
    }

    public function release(int $quantity): self
    {
        $this->assertPositiveQuantity($quantity);

        if ($quantity > $this->reserved) {
            throw InvalidStockRelease::exceedsReserved(
                $quantity,
                $this->reserved,
            );
        }

        return new self($this->onHand, $this->reserved - $quantity);
    }

    public function restock(int $quantity): self
    {
        $this->assertPositiveQuantity($quantity);

        return new self($this->onHand + $quantity, $this->reserved);
    }

    private function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Stock quantity must be positive.');
        }
    }
}
