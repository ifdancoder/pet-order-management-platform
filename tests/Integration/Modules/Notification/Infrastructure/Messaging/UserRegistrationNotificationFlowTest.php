<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use Modules\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use Modules\Notification\Application\Data\NotificationAttempt;
use Modules\Notification\Application\Delivery\NotificationChannelResolver;
use Modules\Notification\Application\Delivery\NotificationDispatcher;
use Modules\Notification\Application\Port\Out\Delivery\INotificationChannel;
use Modules\Notification\Application\Port\Out\Persistence\INotificationRepository;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationStatus;
use Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\NotificationDeliveryModel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Shared\Application\Outbox\OutboxPublisher;
use Shared\Infrastructure\Messaging\RabbitMqMessageConsumer;

uses(DatabaseMigrations::class);

it('turns a published registration event into a pending welcome email', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql');
    $consumer = app(RabbitMqMessageConsumer::class);
    $consumer->consumeOne('notification-user-registered');
    notificationFlowPurgeQueue();
    $user = app(RegisterUserHandler::class)(new RegisterUserCommand(
        email: 'user@example.com',
        password: 'plain-password',
    ));

    $publishResult = app(OutboxPublisher::class)->publishPending();
    $consumed = $consumer->consumeOne('notification-user-registered');

    expect($publishResult->published)->toBe(1)
        ->and($consumed)->toBeTrue();
    $this->assertDatabaseHas('outbox_messages', [
        'event_name' => 'user.registered.v1',
        'aggregate_id' => $user->id()->value(),
    ]);
    $this->assertDatabaseHas('notification_deliveries', [
        'recipient' => 'user@example.com',
        'status' => NotificationStatus::Pending->value,
    ]);
    expect(NotificationDeliveryModel::query()->sole()->data)->toBe([
        'user_id' => $user->id()->value(),
    ]);
});

it('keeps registration committed when welcome email delivery fails', function (): void {
    $this->travelTo('2026-09-28 07:00:00');
    $consumer = app(RabbitMqMessageConsumer::class);
    $consumer->consumeOne('notification-user-registered');
    notificationFlowPurgeQueue();
    $user = app(RegisterUserHandler::class)(new RegisterUserCommand(
        email: 'user@example.com',
        password: 'plain-password',
    ));
    app(OutboxPublisher::class)->publishPending();
    $consumed = $consumer->consumeOne('notification-user-registered');

    expect($consumed)->toBeTrue();
    $this->assertDatabaseHas('notification_deliveries', [
        'recipient' => 'user@example.com',
        'status' => NotificationStatus::Pending->value,
    ]);
    $channel = new class implements INotificationChannel
    {
        public function channel(): NotificationChannel
        {
            return NotificationChannel::Email;
        }

        public function send(NotificationAttempt $notification): void
        {
            throw new RuntimeException('SMTP unavailable');
        }
    };
    $dispatcher = new NotificationDispatcher(
        notifications: app(INotificationRepository::class),
        channels: new NotificationChannelResolver([$channel]),
        batchSize: 10,
        claimTimeoutSeconds: 60,
        maximumAttempts: 3,
        initialRetryDelaySeconds: 5,
        maximumRetryDelaySeconds: 60,
    );

    $result = $dispatcher->dispatchPending();

    expect($result->retrying)->toBe(1)
        ->and(DB::table('outbox_messages')->value('published_at'))->not->toBeNull();
    $this->assertDatabaseHas('users', [
        'id' => $user->id()->value(),
        'email' => 'user@example.com',
    ]);
    $this->assertDatabaseHas('notification_deliveries', [
        'recipient' => 'user@example.com',
        'status' => NotificationStatus::Pending->value,
        'last_error' => 'RuntimeException: SMTP unavailable',
    ]);
    $this->assertDatabaseCount('processed_messages', 1);
});

function notificationFlowPurgeQueue(): void
{
    $connection = notificationFlowRabbitConnection();
    $channel = $connection->channel();
    $queue = notificationFlowStringConfig(
        'messaging.consumers.notification-user-registered.queue',
    );
    $channel->queue_purge($queue);
    $channel->queue_purge($queue.'.dead');
    $channel->close();
    $connection->close();
}

function notificationFlowRabbitConnection(): AMQPStreamConnection
{
    return new AMQPStreamConnection(
        notificationFlowStringConfig('messaging.rabbitmq.host'),
        notificationFlowIntConfig('messaging.rabbitmq.port'),
        notificationFlowStringConfig('messaging.rabbitmq.user'),
        notificationFlowStringConfig('messaging.rabbitmq.password'),
        notificationFlowStringConfig('messaging.rabbitmq.virtual_host'),
    );
}

function notificationFlowStringConfig(string $key): string
{
    $value = config($key);

    if (! is_string($value)) {
        throw new RuntimeException(sprintf('Configuration "%s" must be a string.', $key));
    }

    return $value;
}

function notificationFlowIntConfig(string $key): int
{
    $value = config($key);

    if (! is_int($value)) {
        throw new RuntimeException(sprintf('Configuration "%s" must be an integer.', $key));
    }

    return $value;
}
