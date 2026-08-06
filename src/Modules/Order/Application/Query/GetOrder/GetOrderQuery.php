<?php

declare(strict_types=1);

namespace Modules\Order\Application\Query\GetOrder;

use Modules\Order\Domain\Entity\Order;
use Shared\Application\Bus\Query\IQuery;

/** @implements IQuery<Order> */
final readonly class GetOrderQuery implements IQuery
{
    public function __construct(
        public string $orderId,
    ) {}
}
