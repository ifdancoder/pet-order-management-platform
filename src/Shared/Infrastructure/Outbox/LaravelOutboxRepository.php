<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Outbox;

use DateTimeImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Port\Out\Outbox\IOutboxRepository;
use stdClass;

final readonly class LaravelOutboxRepository implements IOutboxRepository
{
    public function __construct(
        private DatabaseManager $database,
    ) {}

    /** @return list<OutboxMessage> */
    public function claimBatch(int $limit, int $claimTimeoutSeconds): array
    {
        if ($limit < 1 || $claimTimeoutSeconds < 1) {
            throw new RuntimeException('Outbox claim configuration must be positive.');
        }

        $connection = $this->connection();

        return $connection->transaction(function () use (
            $connection,
            $limit,
            $claimTimeoutSeconds,
        ): array {
            $now = now();
            $query = $connection
                ->table('outbox_messages')
                ->whereNull('published_at')
                ->where('available_at', '<=', $now)
                ->where(function ($query) use ($now, $claimTimeoutSeconds): void {
                    $query->whereNull('claimed_at')
                        ->orWhere(
                            'claimed_at',
                            '<=',
                            $now->copy()->subSeconds($claimTimeoutSeconds),
                        );
                })
                ->orderBy('id')
                ->limit($limit);

            if ($connection->getDriverName() === 'pgsql') {
                $query->lock('FOR UPDATE SKIP LOCKED');
            } else {
                $query->lockForUpdate();
            }

            /** @var Collection<int, stdClass> $rows */
            $rows = $query->get();

            if ($rows->isEmpty()) {
                return [];
            }

            $claimToken = Str::uuid7()->toString();
            $ids = $rows->map(
                static fn (stdClass $row): int => (int) $row->id,
            )->all();

            $connection
                ->table('outbox_messages')
                ->whereIn('id', $ids)
                ->update([
                    'claim_token' => $claimToken,
                    'claimed_at' => $now,
                    'attempts' => $connection->raw('attempts + 1'),
                    'last_error' => null,
                ]);

            return array_values($rows->map(
                fn (stdClass $row): OutboxMessage => $this->map(
                    $row,
                    $claimToken,
                    (int) $row->attempts + 1,
                ),
            )->all());
        });
    }

    public function markPublished(string $messageId, string $claimToken): void
    {
        $updated = $this->connection()
            ->table('outbox_messages')
            ->where('message_id', $messageId)
            ->where('claim_token', $claimToken)
            ->whereNull('published_at')
            ->update([
                'published_at' => now(),
                'claimed_at' => null,
                'claim_token' => null,
                'last_error' => null,
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('Outbox message claim was lost before publication.');
        }
    }

    public function release(
        string $messageId,
        string $claimToken,
        string $error,
        int $delaySeconds,
    ): void {
        if ($delaySeconds < 0) {
            throw new RuntimeException('Outbox retry delay cannot be negative.');
        }

        $updated = $this->connection()
            ->table('outbox_messages')
            ->where('message_id', $messageId)
            ->where('claim_token', $claimToken)
            ->whereNull('published_at')
            ->update([
                'available_at' => now()->addSeconds($delaySeconds),
                'claimed_at' => null,
                'claim_token' => null,
                'last_error' => Str::limit($error, 2000, ''),
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('Outbox message claim was lost before release.');
        }
    }

    /** @throws JsonException */
    private function map(
        stdClass $row,
        string $claimToken,
        int $attempts,
    ): OutboxMessage {
        $payload = json_decode(
            (string) $row->payload,
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($payload)) {
            throw new RuntimeException('Outbox message payload must be a JSON object.');
        }

        return new OutboxMessage(
            messageId: (string) $row->message_id,
            eventName: (string) $row->event_name,
            aggregateId: (string) $row->aggregate_id,
            payload: $payload,
            occurredAt: new DateTimeImmutable((string) $row->occurred_at),
            claimToken: $claimToken,
            attempts: $attempts,
            correlationId: $row->correlation_id !== null ? (string) $row->correlation_id : null,
        );
    }

    private function connection(): Connection
    {
        return $this->database->connection();
    }
}
