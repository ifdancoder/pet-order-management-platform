<?php

declare(strict_types=1);

namespace Modules\Order\Application\Command\PlaceOrder;

use Modules\Order\Domain\Entity\Order;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Order> */
final readonly class PlaceOrderCommand implements ICommand
{
    public function __construct(
        public string $orderId,
    ) {}
}
