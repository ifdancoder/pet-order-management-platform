<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Port\Out\Identity;

use Modules\Notification\Domain\ValueObject\NotificationId;

interface INotificationIdGenerator
{
    public function generate(): NotificationId;
}
