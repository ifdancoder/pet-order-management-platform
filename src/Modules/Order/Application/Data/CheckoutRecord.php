<?php

declare(strict_types=1);

namespace Modules\Order\Application\Data;

final readonly class CheckoutRecord
{
    public function __construct(
        public string $idempotencyKey,
        public string $requestHash,
        public ?string $orderId,
        public ?string $inventoryReservationId,
    ) {}

    public function isCompleted(): bool
    {
        return $this->orderId !== null && $this->inventoryReservationId !== null;
    }
}
