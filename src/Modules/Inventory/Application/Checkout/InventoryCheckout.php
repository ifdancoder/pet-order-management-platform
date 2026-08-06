<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Checkout;

use InvalidArgumentException;
use Modules\Inventory\Application\Command\ReserveStock\ReserveStockCommand;
use Modules\Inventory\Application\Command\ReserveStock\ReserveStockHandler;
use Modules\Inventory\Application\Port\In\IInventoryCheckout;
use Modules\Inventory\Application\Port\Out\Persistence\IInventoryItemRepository;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;

final readonly class InventoryCheckout implements IInventoryCheckout
{
    public function __construct(
        private IInventoryItemRepository $inventoryItems,
        private ReserveStockHandler $reserveStock,
    ) {}

    public function isAvailable(array $items): bool
    {
        foreach ($items as $item) {
            try {
                $inventoryItem = $this->inventoryItems->findById(
                    new InventoryItemId($item->inventoryItemId),
                );
            } catch (InvalidArgumentException) {
                return false;
            }

            if ($inventoryItem === null || $inventoryItem->stock()->available() < $item->quantity) {
                return false;
            }
        }

        return true;
    }

    public function reserve(string $reservationKey, array $items): string
    {
        $reservation = ($this->reserveStock)(new ReserveStockCommand(
            reservationKey: $reservationKey,
            items: $items,
        ));

        return $reservation->id()->value();
    }
}
