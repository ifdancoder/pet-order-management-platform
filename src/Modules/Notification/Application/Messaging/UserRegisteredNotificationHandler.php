<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Messaging;

use InvalidArgumentException;
use Modules\Notification\Application\Port\Out\Identity\INotificationIdGenerator;
use Modules\Notification\Application\Port\Out\Persistence\INotificationRepository;
use Modules\Notification\Domain\Entity\NotificationDelivery;
use Modules\Notification\Domain\ValueObject\EmailRecipient;
use Shared\Application\Messaging\IntegrationMessage;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;

final readonly class UserRegisteredNotificationHandler implements IIntegrationMessageHandler
{
    public function __construct(
        private INotificationRepository $notifications,
        private INotificationIdGenerator $ids,
    ) {}

    public function consumerName(): string
    {
        return 'notification-user-registered';
    }

    public function messageNames(): array
    {
        return ['user.registered.v1'];
    }

    public function handle(IntegrationMessage $message): void
    {
        $userId = $message->data['user_id'] ?? null;
        $email = $message->data['email'] ?? null;

        if (! is_string($userId) || ! is_string($email)) {
            throw new InvalidArgumentException(
                'User registered message must contain user_id and email strings.',
            );
        }

        $this->notifications->save(NotificationDelivery::welcomeUser(
            id: $this->ids->generate(),
            sourceMessageId: $message->messageId,
            recipient: new EmailRecipient($email),
            data: ['user_id' => $userId],
        ));
    }
}
