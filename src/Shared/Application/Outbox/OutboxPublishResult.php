<?php

declare(strict_types=1);

namespace Shared\Application\Outbox;

final readonly class OutboxPublishResult
{
    public function __construct(
        public int $claimed,
        public int $published,
        public int $failed,
    ) {}
}
