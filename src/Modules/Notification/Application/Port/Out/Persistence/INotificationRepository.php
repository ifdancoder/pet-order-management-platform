<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Port\Out\Persistence;

use Modules\Notification\Domain\Entity\NotificationDelivery;

interface INotificationRepository
{
    public function save(NotificationDelivery $notification): void;
}
