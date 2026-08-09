<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Entity;

use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationStatus;
use Modules\Notification\Domain\Enum\NotificationTemplate;
use Modules\Notification\Domain\ValueObject\EmailRecipient;
use Modules\Notification\Domain\ValueObject\NotificationId;

final readonly class NotificationDelivery
{
    /** @param array<string, scalar|null> $data */
    public function __construct(
        private NotificationId $id,
        private string $sourceMessageId,
        private EmailRecipient $recipient,
        private NotificationChannel $channel,
        private NotificationTemplate $template,
        private array $data,
        private NotificationStatus $status,
    ) {}

    /** @param array{user_id: string} $data */
    public static function welcomeUser(
        NotificationId $id,
        string $sourceMessageId,
        EmailRecipient $recipient,
        array $data,
    ): self {
        return new self(
            id: $id,
            sourceMessageId: $sourceMessageId,
            recipient: $recipient,
            channel: NotificationChannel::Email,
            template: NotificationTemplate::WelcomeUser,
            data: $data,
            status: NotificationStatus::Pending,
        );
    }

    public function id(): NotificationId
    {
        return $this->id;
    }

    public function sourceMessageId(): string
    {
        return $this->sourceMessageId;
    }

    public function recipient(): EmailRecipient
    {
        return $this->recipient;
    }

    public function channel(): NotificationChannel
    {
        return $this->channel;
    }

    public function template(): NotificationTemplate
    {
        return $this->template;
    }

    /** @return array<string, scalar|null> */
    public function data(): array
    {
        return $this->data;
    }

    public function status(): NotificationStatus
    {
        return $this->status;
    }
}
