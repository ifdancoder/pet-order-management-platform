<?php

declare(strict_types=1);

namespace Modules\Order\Application\Command\TransitionOrder;

use Modules\Order\Application\Enum\OrderTransition;
use Modules\Order\Domain\Entity\Order;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Order> */
final readonly class TransitionOrderCommand implements ICommand
{
    public function __construct(
        public string $orderId,
        public OrderTransition $transition,
    ) {}
}
