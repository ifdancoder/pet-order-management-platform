<?php

declare(strict_types=1);

namespace Modules\Order\Application\Command\AddOrderItem;

use Modules\Order\Application\Data\OrderItemData;
use Modules\Order\Domain\Entity\Order;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Order> */
final readonly class AddOrderItemCommand implements ICommand
{
    public function __construct(
        public string $orderId,
        public OrderItemData $item,
    ) {}
}
