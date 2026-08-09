<?php

declare(strict_types=1);

namespace Shared\Application\Port\Out\Messaging;

interface IProcessedMessageStore
{
    public function record(string $consumer, string $messageId): bool;
}
