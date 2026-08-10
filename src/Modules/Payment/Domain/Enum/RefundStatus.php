<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Enum;

enum RefundStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
