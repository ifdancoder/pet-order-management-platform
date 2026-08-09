<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Delivery;

use LogicException;
use Modules\Notification\Application\Port\Out\Delivery\INotificationChannel;
use Modules\Notification\Domain\Enum\NotificationChannel;

final class NotificationChannelResolver
{
    /** @var array<string, INotificationChannel> */
    private array $channels = [];

    /** @param iterable<INotificationChannel> $channels */
    public function __construct(iterable $channels)
    {
        foreach ($channels as $channel) {
            $name = $channel->channel()->value;

            if (isset($this->channels[$name])) {
                throw new LogicException(sprintf(
                    'Notification channel "%s" has multiple adapters.',
                    $name,
                ));
            }

            $this->channels[$name] = $channel;
        }
    }

    public function resolve(NotificationChannel $channel): INotificationChannel
    {
        return $this->channels[$channel->value]
            ?? throw new LogicException(sprintf(
                'Notification channel "%s" has no adapter.',
                $channel->value,
            ));
    }
}
