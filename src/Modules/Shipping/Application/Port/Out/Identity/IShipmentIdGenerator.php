<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Port\Out\Identity;

use Modules\Shipping\Domain\ValueObject\ShipmentId;

interface IShipmentIdGenerator
{
    public function generate(): ShipmentId;
}
