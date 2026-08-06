<?php

declare(strict_types=1);

namespace Modules\Order\Application\Command\RemoveOrderItem;

use Modules\Order\Domain\Entity\Order;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Order> */
final readonly class RemoveOrderItemCommand implements ICommand
{
    public function __construct(
        public string $orderId,
        public string $inventoryItemId,
    ) {}
}
