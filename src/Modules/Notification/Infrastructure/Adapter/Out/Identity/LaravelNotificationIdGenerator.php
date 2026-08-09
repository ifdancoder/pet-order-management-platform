<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Adapter\Out\Identity;

use Illuminate\Support\Str;
use Modules\Notification\Application\Port\Out\Identity\INotificationIdGenerator;
use Modules\Notification\Domain\ValueObject\NotificationId;

final class LaravelNotificationIdGenerator implements INotificationIdGenerator
{
    public function generate(): NotificationId
    {
        return new NotificationId(Str::uuid7()->toString());
    }
}
