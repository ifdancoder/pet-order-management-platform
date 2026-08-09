<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Data;

final readonly class ShipmentDispatchResult
{
    public function __construct(
        public int $claimed,
        public int $booked,
        public int $retrying,
        public int $failed,
    ) {}
}
