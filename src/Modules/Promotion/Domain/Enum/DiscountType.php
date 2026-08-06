<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\Enum;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
}
