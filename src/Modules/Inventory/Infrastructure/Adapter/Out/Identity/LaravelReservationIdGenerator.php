<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Adapter\Out\Identity;

use Illuminate\Support\Str;
use Modules\Inventory\Application\Port\Out\Identity\IReservationIdGenerator;
use Modules\Inventory\Domain\ValueObject\ReservationId;

final class LaravelReservationIdGenerator implements IReservationIdGenerator
{
    public function generate(): ReservationId
    {
        return new ReservationId(Str::uuid7()->toString());
    }
}
