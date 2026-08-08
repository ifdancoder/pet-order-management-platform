<?php

declare(strict_types=1);

namespace Shared\Application\Outbox;

use DateTimeImmutable;

final readonly class OutboxMessage
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $messageId,
        public string $eventName,
        public string $aggregateId,
        public array $payload,
        public DateTimeImmutable $occurredAt,
        public string $claimToken,
        public int $attempts,
    ) {}
}
