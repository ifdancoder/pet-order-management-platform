<?php

declare(strict_types=1);

namespace Modules\Return\Application\Port\Out\Time;

use DateTimeImmutable;

interface IReturnClock
{
    public function now(): DateTimeImmutable;
}
