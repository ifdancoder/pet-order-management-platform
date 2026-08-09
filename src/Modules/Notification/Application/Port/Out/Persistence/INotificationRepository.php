<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Port\Out\Persistence;

use Modules\Notification\Application\Data\NotificationAttempt;
use Modules\Notification\Domain\Entity\NotificationDelivery;

interface INotificationRepository
{
    public function save(NotificationDelivery $notification): void;

    /** @return list<NotificationAttempt> */
    public function claimBatch(int $limit, int $claimTimeoutSeconds): array;

    public function markSent(string $notificationId, string $claimToken): void;

    public function release(
        string $notificationId,
        string $claimToken,
        string $error,
        int $delaySeconds,
        bool $terminal,
    ): void;
}
