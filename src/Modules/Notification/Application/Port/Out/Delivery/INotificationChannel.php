<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Port\Out\Delivery;

use Modules\Notification\Application\Data\NotificationAttempt;
use Modules\Notification\Domain\Enum\NotificationChannel;

interface INotificationChannel
{
    public function channel(): NotificationChannel;

    public function send(NotificationAttempt $notification): void;
}
