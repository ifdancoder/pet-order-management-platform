<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Exception;

use DomainException;
use Modules\Shipping\Domain\Enum\ShipmentStatus;

final class InvalidShipmentTransition extends DomainException
{
    public static function from(ShipmentStatus $status, string $operation): self
    {
        return new self(sprintf(
            'Cannot %s a shipment with status "%s".',
            $operation,
            $status->value,
        ));
    }
}
