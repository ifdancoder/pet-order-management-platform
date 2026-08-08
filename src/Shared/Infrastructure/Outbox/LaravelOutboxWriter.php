<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Outbox;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use JsonException;
use Shared\Application\Event\IIntegrationEvent;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;

final readonly class LaravelOutboxWriter implements IOutboxWriter
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    /** @throws JsonException */
    public function record(IIntegrationEvent $event): void
    {
        $now = now()->toDateTimeString();

        $this->connection->table('outbox_messages')->insert([
            'message_id' => Str::uuid7()->toString(),
            'event_name' => $event->name(),
            'aggregate_id' => $event->aggregateId(),
            'payload' => json_encode($event->payload(), JSON_THROW_ON_ERROR),
            'occurred_at' => $now,
            'available_at' => $now,
            'attempts' => 0,
        ]);
    }
}
