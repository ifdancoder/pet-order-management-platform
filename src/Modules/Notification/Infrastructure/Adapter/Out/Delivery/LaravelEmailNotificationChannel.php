<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Adapter\Out\Delivery;

use Illuminate\Contracts\Mail\Mailer;
use InvalidArgumentException;
use Modules\Notification\Application\Data\NotificationAttempt;
use Modules\Notification\Application\Port\Out\Delivery\INotificationChannel;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationTemplate;
use Modules\Notification\Infrastructure\Mail\WelcomeUserMail;

final readonly class LaravelEmailNotificationChannel implements INotificationChannel
{
    public function __construct(
        private Mailer $mailer,
    ) {}

    public function channel(): NotificationChannel
    {
        return NotificationChannel::Email;
    }

    public function send(NotificationAttempt $notification): void
    {
        $userId = $notification->data['user_id'] ?? null;

        if (! is_string($userId)) {
            throw new InvalidArgumentException(
                'Welcome email data must contain a user_id string.',
            );
        }

        $mail = match ($notification->template) {
            NotificationTemplate::WelcomeUser => new WelcomeUserMail($userId),
        };

        $this->mailer
            ->to($notification->recipient->value())
            ->send($mail);
    }
}
