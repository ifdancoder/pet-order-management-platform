<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Enum;

enum NotificationStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
}
