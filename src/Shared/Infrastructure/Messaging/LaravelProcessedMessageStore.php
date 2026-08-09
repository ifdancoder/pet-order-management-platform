<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Messaging;

use Illuminate\Database\ConnectionInterface;
use Shared\Application\Port\Out\Messaging\IProcessedMessageStore;

final readonly class LaravelProcessedMessageStore implements IProcessedMessageStore
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    public function record(string $consumer, string $messageId): bool
    {
        return $this->connection->table('processed_messages')->insertOrIgnore([
            'consumer' => $consumer,
            'message_id' => $messageId,
            'processed_at' => now(),
        ]) === 1;
    }
}
