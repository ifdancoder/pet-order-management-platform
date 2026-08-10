<?php

declare(strict_types=1);

namespace Modules\Return\Infrastructure\Adapter\Out\Time;

use DateTimeImmutable;
use Modules\Return\Application\Port\Out\Time\IReturnClock;

final readonly class SystemReturnClock implements IReturnClock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable;
    }
}
