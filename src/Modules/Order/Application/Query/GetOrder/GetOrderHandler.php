<?php

declare(strict_types=1);

namespace Modules\Order\Application\Query\GetOrder;

use Modules\Order\Application\Exception\OrderNotFound;
use Modules\Order\Application\Port\Out\Persistence\IOrderRepository;
use Modules\Order\Domain\Entity\Order;
use Modules\Order\Domain\ValueObject\OrderId;

final readonly class GetOrderHandler
{
    public function __construct(
        private IOrderRepository $orders,
    ) {}

    public function __invoke(GetOrderQuery $query): Order
    {
        return $this->orders->findById(new OrderId($query->orderId))
            ?? throw OrderNotFound::withId($query->orderId);
    }
}
