<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Data;

use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationTemplate;
use Modules\Notification\Domain\ValueObject\EmailRecipient;

final readonly class NotificationAttempt
{
    /** @param array<string, scalar|null> $data */
    public function __construct(
        public string $notificationId,
        public string $claimToken,
        public EmailRecipient $recipient,
        public NotificationChannel $channel,
        public NotificationTemplate $template,
        public array $data,
        public int $attempts,
    ) {}
}
