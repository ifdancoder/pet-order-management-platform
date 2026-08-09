<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Enum;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Booked = 'booked';
    case Failed = 'failed';
}
