<?php

declare(strict_types=1);

namespace Modules\Shipping\Infrastructure\Adapter\Out\Identity;

use Illuminate\Support\Str;
use Modules\Shipping\Application\Port\Out\Identity\IShipmentIdGenerator;
use Modules\Shipping\Domain\ValueObject\ShipmentId;

final class LaravelShipmentIdGenerator implements IShipmentIdGenerator
{
    public function generate(): ShipmentId
    {
        return new ShipmentId(Str::uuid7()->toString());
    }
}
