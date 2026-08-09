<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Modules\Notification\Application\Data\NotificationAttempt;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationTemplate;
use Modules\Notification\Domain\ValueObject\EmailRecipient;
use Modules\Notification\Infrastructure\Adapter\Out\Delivery\LaravelEmailNotificationChannel;
use Modules\Notification\Infrastructure\Mail\WelcomeUserMail;

it('sends the welcome mailable to the delivery recipient', function (): void {
    Mail::fake();
    $channel = app(LaravelEmailNotificationChannel::class);

    $channel->send(new NotificationAttempt(
        notificationId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4fd0',
        claimToken: '018f22e2-7c2a-7a33-8c4c-4ea690ad4fd1',
        recipient: new EmailRecipient('user@example.com'),
        channel: NotificationChannel::Email,
        template: NotificationTemplate::WelcomeUser,
        data: ['user_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4fd2'],
        attempts: 1,
    ));

    Mail::assertSent(
        WelcomeUserMail::class,
        static fn (WelcomeUserMail $mail): bool => $mail->hasTo('user@example.com')
            && $mail->userId === '018f22e2-7c2a-7a33-8c4c-4ea690ad4fd2',
    );
});
