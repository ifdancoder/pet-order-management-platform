<?php

declare(strict_types=1);

namespace Shared\Application\Port\Out\Messaging;

use Shared\Application\Outbox\OutboxMessage;

interface IMessagePublisher
{
    public function publish(OutboxMessage $message): void;
}
