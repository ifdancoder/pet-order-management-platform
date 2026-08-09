<?php

declare(strict_types=1);

use Modules\Notification\Domain\Entity\NotificationDelivery;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationStatus;
use Modules\Notification\Domain\Enum\NotificationTemplate;
use Modules\Notification\Domain\ValueObject\EmailRecipient;
use Modules\Notification\Domain\ValueObject\NotificationId;

it('creates a pending welcome email from a registration message', function (): void {
    $notification = NotificationDelivery::welcomeUser(
        id: new NotificationId('018f22e2-7c2a-7a33-8c4c-4ea690ad4fb0'),
        sourceMessageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4fb1',
        recipient: new EmailRecipient('user@example.com'),
        data: ['user_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4fb2'],
    );

    expect($notification->channel())->toBe(NotificationChannel::Email)
        ->and($notification->template())->toBe(NotificationTemplate::WelcomeUser)
        ->and($notification->status())->toBe(NotificationStatus::Pending)
        ->and($notification->data())->toBe([
            'user_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4fb2',
        ]);
});
