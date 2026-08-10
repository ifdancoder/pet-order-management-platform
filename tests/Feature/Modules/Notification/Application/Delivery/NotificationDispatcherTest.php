<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Notification\Application\Data\NotificationAttempt;
use Modules\Notification\Application\Delivery\NotificationChannelResolver;
use Modules\Notification\Application\Delivery\NotificationDispatcher;
use Modules\Notification\Application\Port\Out\Delivery\INotificationChannel;
use Modules\Notification\Application\Port\Out\Persistence\INotificationRepository;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationStatus;
use Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\NotificationDeliveryModel;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

uses(LazilyRefreshDatabase::class);

it('marks a notification as sent after its channel accepts it', function (): void {
    $notification = NotificationDeliveryModel::factory()->create();
    $channel = notificationDispatcherTestChannel();
    $logger = new NotificationDispatcherTestLogger;

    $result = notificationDispatcherTestSubject($channel, $logger)->dispatchPending();

    expect($result->claimed)->toBe(1)
        ->and($result->sent)->toBe(1)
        ->and($result->retrying)->toBe(0)
        ->and($channel->sent)->toHaveCount(1);
    $this->assertDatabaseHas('notification_deliveries', [
        'id' => $notification->getKey(),
        'status' => NotificationStatus::Sent->value,
        'claim_token' => null,
        'last_error' => null,
    ]);
    expect($logger->records)->toHaveCount(1)
        ->and($logger->records[0]['message'])->toBe('Notification delivery succeeded.')
        ->and($logger->records[0]['context'])->toHaveKey('notification_id');
});

it('releases a transient channel failure with exponential backoff', function (): void {
    $this->travelTo('2026-09-28 07:00:00');
    $notification = NotificationDeliveryModel::factory()->create();
    $channel = notificationDispatcherTestChannel(failure: 'SMTP unavailable');
    $logger = new NotificationDispatcherTestLogger;

    $result = notificationDispatcherTestSubject($channel, $logger)->dispatchPending();

    expect($result->retrying)->toBe(1)
        ->and($result->failed)->toBe(0);
    $this->assertDatabaseHas('notification_deliveries', [
        'id' => $notification->getKey(),
        'status' => NotificationStatus::Pending->value,
        'attempts' => 1,
        'available_at' => '2026-09-28 07:00:05',
        'claimed_at' => null,
        'claim_token' => null,
        'last_error' => 'RuntimeException: SMTP unavailable',
    ]);
    expect($logger->records)->toHaveCount(1)
        ->and($logger->records[0]['message'])->toBe('Notification delivery retrying.')
        ->and($logger->records[0]['context'])->toHaveKeys(['notification_id', 'attempts', 'exception']);
});

it('marks a notification failed after its final delivery attempt', function (): void {
    $notification = NotificationDeliveryModel::factory()->create([
        'attempts' => 2,
    ]);
    $channel = notificationDispatcherTestChannel(failure: 'Mailbox rejected');
    $logger = new NotificationDispatcherTestLogger;

    $result = notificationDispatcherTestSubject($channel, $logger)->dispatchPending();

    expect($result->retrying)->toBe(0)
        ->and($result->failed)->toBe(1);
    $this->assertDatabaseHas('notification_deliveries', [
        'id' => $notification->getKey(),
        'status' => NotificationStatus::Failed->value,
        'attempts' => 3,
        'claimed_at' => null,
        'claim_token' => null,
        'last_error' => 'RuntimeException: Mailbox rejected',
    ]);
    expect($logger->records)->toHaveCount(1)
        ->and($logger->records[0]['message'])->toBe('Notification delivery failed.')
        ->and($logger->records[0]['context'])->toHaveKeys(['notification_id', 'attempts', 'exception']);
});

final class NotificationDispatcherTestLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string, context: array<string, mixed>}> */
    public array $records = [];

    /** @param array<string, mixed> $context */
    public function log(
        mixed $level,
        Stringable|string $message,
        array $context = [],
    ): void {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}

function notificationDispatcherTestSubject(
    INotificationChannel $channel,
    ?LoggerInterface $logger = null,
): NotificationDispatcher {
    return new NotificationDispatcher(
        notifications: app(INotificationRepository::class),
        channels: new NotificationChannelResolver([$channel]),
        logger: $logger ?? new NotificationDispatcherTestLogger,
        batchSize: 10,
        claimTimeoutSeconds: 60,
        maximumAttempts: 3,
        initialRetryDelaySeconds: 5,
        maximumRetryDelaySeconds: 60,
    );
}

function notificationDispatcherTestChannel(
    ?string $failure = null,
): INotificationChannel {
    return new class($failure) implements INotificationChannel
    {
        /** @var list<NotificationAttempt> */
        public array $sent = [];

        public function __construct(
            private readonly ?string $failure,
        ) {}

        public function channel(): NotificationChannel
        {
            return NotificationChannel::Email;
        }

        public function send(NotificationAttempt $notification): void
        {
            if ($this->failure !== null) {
                throw new RuntimeException($this->failure);
            }

            $this->sent[] = $notification;
        }
    };
}
