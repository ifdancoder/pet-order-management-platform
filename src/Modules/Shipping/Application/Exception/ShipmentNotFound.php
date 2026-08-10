<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Exception;

use RuntimeException;

final class ShipmentNotFound extends RuntimeException
{
    public static function forOrder(string $orderId): self
    {
        return new self(sprintf(
            'Shipment for order "%s" was not found.',
            $orderId,
        ));
    }
}
