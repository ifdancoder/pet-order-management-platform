<?php

declare(strict_types=1);

namespace Shared\Application\Port\Out\Outbox;

use Shared\Application\Outbox\OutboxMessage;

interface IOutboxRepository
{
    /** @return list<OutboxMessage> */
    public function claimBatch(int $limit, int $claimTimeoutSeconds): array;

    public function markPublished(string $messageId, string $claimToken): void;

    public function release(
        string $messageId,
        string $claimToken,
        string $error,
        int $delaySeconds,
    ): void;
}
