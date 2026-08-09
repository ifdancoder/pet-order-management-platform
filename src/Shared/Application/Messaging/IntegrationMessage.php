<?php

declare(strict_types=1);

namespace Shared\Application\Messaging;

use DateTimeImmutable;

final readonly class IntegrationMessage
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public string $messageId,
        public string $name,
        public string $aggregateId,
        public DateTimeImmutable $occurredAt,
        public array $data,
    ) {}
}
