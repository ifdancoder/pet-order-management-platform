<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Enum;

enum NotificationChannel: string
{
    case Email = 'email';
}
