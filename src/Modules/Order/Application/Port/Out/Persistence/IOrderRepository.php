<?php

declare(strict_types=1);

namespace Modules\Order\Application\Port\Out\Persistence;

use Modules\Order\Domain\Entity\Order;
use Modules\Order\Domain\ValueObject\OrderId;

interface IOrderRepository
{
    public function save(Order $order): void;

    public function findById(OrderId $orderId): ?Order;

    public function findByIdForUpdate(OrderId $orderId): ?Order;
}
