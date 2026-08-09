<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonException;
use Modules\Notification\Application\Data\NotificationAttempt;
use Modules\Notification\Application\Port\Out\Persistence\INotificationRepository;
use Modules\Notification\Domain\Entity\NotificationDelivery;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationStatus;
use Modules\Notification\Domain\Enum\NotificationTemplate;
use Modules\Notification\Domain\ValueObject\EmailRecipient;
use Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\NotificationDeliveryMapper;
use Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\NotificationDeliveryModel;
use RuntimeException;
use stdClass;

final readonly class EloquentNotificationRepository implements INotificationRepository
{
    public function __construct(
        private NotificationDeliveryMapper $mapper,
        private DatabaseManager $database,
    ) {}

    public function save(NotificationDelivery $notification): void
    {
        $model = NotificationDeliveryModel::query()->find(
            $notification->id()->value(),
        ) ?? new NotificationDeliveryModel;
        $this->mapper->mapToModel($notification, $model);

        if (! $model->exists) {
            $model->setAttribute('available_at', now());
        }

        $model->save();
    }

    public function claimBatch(int $limit, int $claimTimeoutSeconds): array
    {
        if ($limit < 1 || $claimTimeoutSeconds < 1) {
            throw new RuntimeException('Notification claim configuration must be positive.');
        }

        $connection = $this->connection();

        return $connection->transaction(function () use (
            $connection,
            $limit,
            $claimTimeoutSeconds,
        ): array {
            $now = now();
            $query = $connection
                ->table('notification_deliveries')
                ->where('status', NotificationStatus::Pending->value)
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
                static fn (stdClass $row): string => (string) $row->id,
            )->all();

            $connection
                ->table('notification_deliveries')
                ->whereIn('id', $ids)
                ->update([
                    'claim_token' => $claimToken,
                    'claimed_at' => $now,
                    'attempts' => $connection->raw('attempts + 1'),
                    'last_error' => null,
                    'updated_at' => $now,
                ]);

            return array_values($rows->map(
                fn (stdClass $row): NotificationAttempt => new NotificationAttempt(
                    notificationId: (string) $row->id,
                    claimToken: $claimToken,
                    recipient: new EmailRecipient((string) $row->recipient),
                    channel: NotificationChannel::from((string) $row->channel),
                    template: NotificationTemplate::from((string) $row->template),
                    data: $this->decodeData($row->data),
                    attempts: (int) $row->attempts + 1,
                ),
            )->all());
        });
    }

    public function markSent(string $notificationId, string $claimToken): void
    {
        $updated = $this->connection()
            ->table('notification_deliveries')
            ->where('id', $notificationId)
            ->where('claim_token', $claimToken)
            ->where('status', NotificationStatus::Pending->value)
            ->update([
                'status' => NotificationStatus::Sent->value,
                'sent_at' => now(),
                'claimed_at' => null,
                'claim_token' => null,
                'last_error' => null,
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('Notification claim was lost before completion.');
        }
    }

    public function release(
        string $notificationId,
        string $claimToken,
        string $error,
        int $delaySeconds,
        bool $terminal,
    ): void {
        if ($delaySeconds < 0) {
            throw new RuntimeException('Notification retry delay cannot be negative.');
        }

        $updated = $this->connection()
            ->table('notification_deliveries')
            ->where('id', $notificationId)
            ->where('claim_token', $claimToken)
            ->where('status', NotificationStatus::Pending->value)
            ->update([
                'status' => $terminal
                    ? NotificationStatus::Failed->value
                    : NotificationStatus::Pending->value,
                'available_at' => now()->addSeconds($delaySeconds),
                'claimed_at' => null,
                'claim_token' => null,
                'last_error' => Str::limit($error, 2000, ''),
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('Notification claim was lost before release.');
        }
    }

    /**
     * @return array<string, scalar|null>
     *
     * @throws JsonException
     */
    private function decodeData(mixed $value): array
    {
        $data = is_string($value)
            ? json_decode($value, true, flags: JSON_THROW_ON_ERROR)
            : $value;

        if (! is_array($data)) {
            throw new RuntimeException('Notification data must be a JSON object.');
        }

        foreach ($data as $key => $item) {
            if (! is_string($key) || (! is_scalar($item) && $item !== null)) {
                throw new RuntimeException('Notification data must contain scalar values.');
            }
        }

        return $data;
    }

    private function connection(): Connection
    {
        return $this->database->connection();
    }
}
