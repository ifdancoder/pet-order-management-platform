<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Enum;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Placed = 'placed';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case PaymentFailed = 'payment_failed';
}
