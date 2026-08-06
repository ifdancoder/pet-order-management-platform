<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Checkout;

use Modules\Inventory\Application\Data\StockRequest;
use Modules\Inventory\Application\Port\In\IInventoryCheckout;
use Modules\Order\Application\Data\OrderItemData;
use Modules\Order\Application\Port\Out\Checkout\IInventoryCheckoutGateway;

final readonly class InventoryCheckoutGateway implements IInventoryCheckoutGateway
{
    public function __construct(
        private IInventoryCheckout $inventory,
    ) {}

    public function isAvailable(array $items): bool
    {
        return $this->inventory->isAvailable($this->stockRequests($items));
    }

    public function reserve(string $reservationKey, array $items): string
    {
        return $this->inventory->reserve(
            $reservationKey,
            $this->stockRequests($items),
        );
    }

    /**
     * @param  list<OrderItemData>  $items
     * @return list<StockRequest>
     */
    private function stockRequests(array $items): array
    {
        return array_map(
            static fn (OrderItemData $item): StockRequest => new StockRequest(
                inventoryItemId: $item->inventoryItemId,
                quantity: $item->quantity,
            ),
            $items,
        );
    }
}
