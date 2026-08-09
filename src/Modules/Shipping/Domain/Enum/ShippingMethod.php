<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Enum;

enum ShippingMethod: string
{
    case Courier = 'courier';
    case Express = 'express';
    case PickupPoint = 'pickup_point';
    case International = 'international';
}
