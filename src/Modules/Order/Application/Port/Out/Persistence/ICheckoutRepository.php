<?php

declare(strict_types=1);

namespace Modules\Order\Application\Port\Out\Persistence;

use Modules\Order\Application\Data\CheckoutRecord;

interface ICheckoutRepository
{
    public function claim(string $idempotencyKey, string $requestHash): bool;

    public function findByKey(string $idempotencyKey): ?CheckoutRecord;

    public function complete(
        string $idempotencyKey,
        string $orderId,
        string $inventoryReservationId,
    ): void;
}
