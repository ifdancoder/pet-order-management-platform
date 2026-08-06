<?php

declare(strict_types=1);

namespace Modules\Order\Application\Command\CreateOrderDraft;

use Modules\Order\Application\Data\OrderItemData;
use Modules\Order\Domain\Entity\Order;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Order> */
final readonly class CreateOrderDraftCommand implements ICommand
{
    /** @param list<OrderItemData> $items */
    public function __construct(
        public string $customerId,
        public string $currency,
        public array $items = [],
    ) {}
}
