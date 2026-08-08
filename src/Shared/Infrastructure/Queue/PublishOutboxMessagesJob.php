<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Shared\Application\Outbox\OutboxPublisher;

final class PublishOutboxMessagesJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 20;

    public int $uniqueFor = 30;

    public function handle(OutboxPublisher $publisher): void
    {
        $publisher->publishPending();
    }

    public function uniqueId(): string
    {
        return 'outbox-publisher';
    }
}
